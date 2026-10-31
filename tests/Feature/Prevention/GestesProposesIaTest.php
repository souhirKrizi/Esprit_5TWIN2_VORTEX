<?php

namespace Tests\Feature\Prevention;

use App\Models\FicheConseil;
use App\Models\GesteCle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GestesProposesIaTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'api.groq.com/*';

    private User $admin;

    private FicheConseil $fiche;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['prevention.groq.cle' => 'cle-de-test']);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->fiche = FicheConseil::create([
            'titre' => 'Se préparer à une coupure',
            'resume' => 'Les préparatifs simples avant une coupure.',
            'contenu' => 'Gardez vos téléphones chargés et le frigo fermé.',
            'thematique' => 'coupure_courant',
            'niveau_alerte' => 'orange',
            'statut' => 'publiee',
        ]);
    }

    private function page(): string
    {
        return '/admin/conseils/'.$this->fiche->id;
    }

    private function reponseIa(array|string $contenu): array
    {
        return ['choices' => [['message' => ['content' => is_string($contenu) ? $contenu : json_encode($contenu)]]]];
    }

    private function gestesProposes(): array
    {
        return [
            ['intitule' => 'Garder le frigo fermé', 'moment' => 'pendant'],
            ['intitule' => 'Charger les téléphones', 'moment' => 'avant'],
            ['intitule' => 'Rebrancher les appareils un par un', 'moment' => 'apres'],
        ];
    }

    private function fauxGestes(array $gestes): void
    {
        Http::fake([self::URL => Http::response($this->reponseIa(['gestes' => $gestes]))]);
    }

    private function proposer(): void
    {
        $this->actingAs($this->admin)->post($this->page().'/gestes/propositions')->assertRedirect($this->page().'#gestes-ia')->assertSessionHas('success');
    }

    private function gesteExistant(string $intitule = 'Geste déjà présent', int $ordre = 1): void
    {
        $this->fiche->gestes()->create(['intitule' => $intitule, 'moment' => 'avant', 'ordre' => $ordre]);
    }

    public function test_les_propositions_s_affichent_sans_rien_enregistrer(): void
    {
        $this->fauxGestes($this->gestesProposes());

        $this->actingAs($this->admin)->get($this->page())->assertOk()->assertSee('Gestes proposés par l’IA')->assertSee('Proposer des gestes');

        $this->proposer();

        $this->assertDatabaseCount('gestes_cles', 0);
        $this->actingAs($this->admin)->get($this->page())
            ->assertSeeInOrder(['Charger les téléphones', 'Garder le frigo fermé', 'Rebrancher les appareils un par un'])
            ->assertSee('Proposer d’autres gestes')
            ->assertSee('Ignorer ces propositions');
    }

    public function test_seuls_les_gestes_coches_sont_ajoutes_avec_le_texte_corrige(): void
    {
        $this->fauxGestes($this->gestesProposes());
        $this->gesteExistant('Préparer une lampe torche', 4);
        $this->proposer();

        $this->actingAs($this->admin)->post($this->page().'/gestes/propositions/ajouter', ['gestes' => [
            0 => ['choisi' => '1', 'intitule' => 'Charger tous les téléphones', 'moment' => 'avant'],
            1 => ['intitule' => 'Garder le frigo fermé', 'moment' => 'pendant'],
            2 => ['choisi' => '1', 'intitule' => 'Rebrancher les appareils un par un', 'moment' => 'apres'],
        ]])->assertRedirect($this->page())->assertSessionHas('success', '2 gestes ajoutés à la fiche.');

        $this->assertDatabaseHas('gestes_cles', ['fiche_conseil_id' => $this->fiche->id, 'intitule' => 'Charger tous les téléphones', 'moment' => 'avant', 'ordre' => 5]);
        $this->assertDatabaseHas('gestes_cles', ['fiche_conseil_id' => $this->fiche->id, 'intitule' => 'Rebrancher les appareils un par un', 'moment' => 'apres', 'ordre' => 1]);
        $this->assertDatabaseMissing('gestes_cles', ['intitule' => 'Garder le frigo fermé']);

        // Les propositions disparaissent une fois le choix fait.
        $this->actingAs($this->admin)->get($this->page())->assertSee('Proposer des gestes')->assertDontSee('Ignorer ces propositions');
    }

    public function test_le_choix_est_controle(): void
    {
        $url = $this->page().'/gestes/propositions/ajouter';
        $this->gesteExistant();

        $this->actingAs($this->admin)->post($url, ['gestes' => [0 => ['intitule' => 'Sans case cochée', 'moment' => 'avant']]])
            ->assertRedirect($this->page().'#gestes-ia')
            ->assertSessionHasErrors(['gestes' => 'Cochez au moins un geste à ajouter.']);

        $this->actingAs($this->admin)->post($url, ['gestes' => [0 => ['choisi' => '1', 'intitule' => 'Eau', 'moment' => 'avant']]])
            ->assertSessionHasErrors(['gestes.0.intitule' => 'Le champ « geste » doit contenir au moins 5 caractères.']);

        $this->actingAs($this->admin)->post($url, ['gestes' => [0 => ['choisi' => '1', 'intitule' => 'Geste déjà présent', 'moment' => 'avant']]])
            ->assertSessionHasErrors(['gestes.0.intitule' => 'Ce geste existe déjà dans cette fiche.']);

        $this->actingAs($this->admin)->post($url, ['gestes' => [
            0 => ['choisi' => '1', 'intitule' => 'Fermer les volets', 'moment' => 'avant'],
            1 => ['choisi' => '1', 'intitule' => 'fermer les volets', 'moment' => 'pendant'],
        ]])->assertSessionHasErrors(['gestes.0.intitule' => 'Ce geste est présent deux fois dans votre sélection.']);

        $this->actingAs($this->admin)->post($url, ['gestes' => [0 => ['choisi' => '1', 'intitule' => 'Fermer les volets', 'moment' => 'demain']]])
            ->assertSessionHasErrors('gestes.0.moment');

        $this->actingAs($this->admin)->post($url, ['gestes' => [0 => ['choisi' => '1', 'intitule' => ['pas', 'un', 'texte'], 'moment' => 'avant']]])
            ->assertSessionHasErrors('gestes.0.intitule');

        $this->assertDatabaseCount('gestes_cles', 1);
    }

    public function test_apres_une_erreur_les_corrections_sont_conservees(): void
    {
        $this->fauxGestes($this->gestesProposes());
        $this->proposer();

        $this->actingAs($this->admin)->followingRedirects()->post($this->page().'/gestes/propositions/ajouter', ['gestes' => [
            0 => ['choisi' => '1', 'intitule' => 'Eau', 'moment' => 'avant'],
            1 => ['intitule' => 'Garder le frigo fermé', 'moment' => 'pendant'],
            2 => ['choisi' => '1', 'intitule' => 'Rebrancher les appareils un par un', 'moment' => 'apres'],
        ]])->assertOk()
            ->assertSee('doit contenir au moins 5 caractères')
            ->assertSee('>Eau</textarea>', false);

        $this->assertDatabaseCount('gestes_cles', 0);
    }

    public function test_les_propositions_de_l_ia_sont_filtrees(): void
    {
        $this->gesteExistant();
        $this->fauxGestes([
            ['intitule' => 'Geste déjà présent', 'moment' => 'avant'],
            ['intitule' => 'GESTE DÉJÀ PRÉSENT', 'moment' => 'pendant'],
            ['intitule' => 'Moment inconnu du tout', 'moment' => 'demain'],
            ['intitule' => 'Non', 'moment' => 'avant'],
            ['intitule' => str_repeat('a', 151), 'moment' => 'avant'],
            ['intitule' => '<b>Fermer</b> les volets', 'moment' => 'avant'],
            ['intitule' => 'Fermer les volets', 'moment' => 'apres'],
            'pas un tableau',
        ]);

        $this->proposer();

        $this->assertSame(
            [['intitule' => 'Fermer les volets', 'moment' => 'avant']],
            session('gestes_ia.'.$this->fiche->id)
        );
    }

    public function test_aucune_proposition_valide_donne_un_message_clair(): void
    {
        Http::fakeSequence(self::URL)->push($this->reponseIa('pas du json'))->push($this->reponseIa(['gestes' => []]));

        foreach (range(1, 2) as $essai) {
            $this->actingAs($this->admin)->post($this->page().'/gestes/propositions')
                ->assertSessionHas('error', 'L’IA n’a trouvé aucun nouveau geste à proposer pour cette fiche.');
        }

        Http::assertSentCount(2);
    }

    public function test_le_contenu_de_la_fiche_et_les_gestes_existants_sont_envoyes(): void
    {
        $this->gesteExistant();
        $this->fauxGestes($this->gestesProposes());

        $this->proposer();

        Http::assertSent(function (Request $requete) {
            $donnees = json_decode($requete->body(), true)['messages'][1]['content'];

            return str_contains($donnees, 'Gardez vos téléphones chargés') && str_contains($donnees, 'Geste déjà présent');
        });
    }

    public function test_une_erreur_de_l_api_garde_les_propositions(): void
    {
        Http::fakeSequence(self::URL)->push($this->reponseIa(['gestes' => $this->gestesProposes()]))->push([], 500);

        $this->proposer();
        $avant = session('gestes_ia.'.$this->fiche->id);

        $this->actingAs($this->admin)->post($this->page().'/gestes/propositions')
            ->assertSessionHas('error', 'Le service de conseils est indisponible pour le moment. Réessayez plus tard.');

        $this->assertSame($avant, session('gestes_ia.'.$this->fiche->id));
    }

    public function test_ignorer_retire_les_propositions(): void
    {
        $this->fauxGestes($this->gestesProposes());
        $this->proposer();

        $this->actingAs($this->admin)->delete($this->page().'/gestes/propositions')->assertRedirect($this->page().'#gestes-ia');

        $this->assertNull(session('gestes_ia.'.$this->fiche->id));
        $this->assertDatabaseCount('gestes_cles', 0);
    }

    public function test_cinq_demandes_par_minute_maximum(): void
    {
        $this->fauxGestes($this->gestesProposes());

        foreach (range(1, 5) as $essai) {
            $this->proposer();
        }

        $this->actingAs($this->admin)->post($this->page().'/gestes/propositions')->assertSessionHas('error');
        Http::assertSentCount(5);
    }

    public function test_seul_l_admin_utilise_les_propositions(): void
    {
        foreach (['user', 'agent-campagne'] as $role) {
            $compte = User::factory()->create(['role' => $role]);

            $this->actingAs($compte)->post($this->page().'/gestes/propositions')->assertForbidden();
            $this->actingAs($compte)->post($this->page().'/gestes/propositions/ajouter', ['gestes' => [0 => ['choisi' => '1', 'intitule' => 'Geste interdit', 'moment' => 'avant']]])->assertForbidden();
            $this->actingAs($compte)->delete($this->page().'/gestes/propositions')->assertForbidden();
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('gestes_cles', 0);
    }

    public function test_les_gestes_ajoutes_apparaissent_chez_le_citoyen(): void
    {
        $this->fauxGestes($this->gestesProposes());
        $this->proposer();

        $this->actingAs($this->admin)->post($this->page().'/gestes/propositions/ajouter', ['gestes' => [
            0 => ['choisi' => '1', 'intitule' => 'Garder le frigo fermé', 'moment' => 'pendant'],
        ]])->assertSessionHas('success', '1 geste ajouté à la fiche.');

        $citoyen = User::factory()->create(['role' => 'user']);
        $this->actingAs($citoyen)->get('/user/conseils/'.$this->fiche->id)->assertSee('Garder le frigo fermé');
        $this->assertSame(1, GesteCle::count());
    }
}
