<?php

namespace Tests\Feature\Prevention;

use App\Models\EquipementSensible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConseilsIaTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'api.groq.com/*';

    private User $citoyen;

    private EquipementSensible $equipement;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['prevention.groq.cle' => 'cle-de-test']);

        $this->citoyen = User::factory()->create(['role' => 'user', 'email' => 'prive@example.com']);
        $this->equipement = $this->equipementDe($this->citoyen);
    }

    private function equipementDe(User $user, array $attributs = []): EquipementSensible
    {
        $equipement = new EquipementSensible(array_merge([
            'nom' => 'Frigo de la cuisine',
            'type' => 'refrigerateur',
            'sensibilite' => 'les_deux',
            'autonomie_heures' => 4,
            'description' => 'Le frigo contient mon insuline.',
        ], $attributs));
        $equipement->user_id = $user->id;
        $equipement->save();

        return $equipement;
    }

    private function url(): string
    {
        return '/user/mes-equipements/'.$this->equipement->id;
    }

    private function conseils(array $surcharge = []): array
    {
        return array_merge([
            'priorite' => 'elevee',
            'raison' => 'L’insuline doit rester au froid.',
            'avant' => ['Préparer un sac isotherme'],
            'pendant' => ['Garder le frigo fermé'],
            'apres' => ['Vérifier l’insuline'],
            'a_eviter' => ['Ouvrir le frigo inutilement'],
        ], $surcharge);
    }

    private function reponseIa(array|string $contenu): array
    {
        return ['choices' => [['message' => ['content' => is_string($contenu) ? $contenu : json_encode($contenu)]]]];
    }

    public function test_sans_conseils_la_page_propose_de_les_generer(): void
    {
        $this->actingAs($this->citoyen)->get('/user/mes-equipements')->assertSee('À générer');
        $this->actingAs($this->citoyen)->get($this->url())->assertOk()->assertSee('Générer mes conseils');
        Http::assertNothingSent();
    }

    public function test_les_conseils_generes_sont_enregistres_et_affiches(): void
    {
        Http::fake([self::URL => Http::response($this->reponseIa($this->conseils()))]);

        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertRedirect($this->url())->assertSessionHas('success');

        $this->equipement->refresh();
        $this->assertSame('elevee', $this->equipement->conseils_ia['priorite']);
        $this->assertNotNull($this->equipement->conseils_ia_le);

        $this->actingAs($this->citoyen)->get($this->url())
            ->assertSee('Priorité élevée')
            ->assertSee('L’insuline doit rester au froid.')
            ->assertSee('Préparer un sac isotherme')
            ->assertSee('Ouvrir le frigo inutilement')
            ->assertSee('Générer de nouveaux conseils');
        $this->actingAs($this->citoyen)->get('/user/mes-equipements')->assertSee('Conseils prêts');
    }

    public function test_seules_les_informations_de_l_equipement_sont_envoyees(): void
    {
        Http::fake([self::URL => Http::response($this->reponseIa($this->conseils()))]);

        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia');

        Http::assertSent(function (Request $requete) {
            $corps = $requete->body();

            return $requete->hasHeader('Authorization', 'Bearer cle-de-test')
                && str_contains($corps, 'insuline')
                && ! str_contains($corps, 'prive@example.com')
                && ! str_contains($corps, $this->citoyen->name);
        });
    }

    public function test_une_reponse_incomplete_est_refusee_et_garde_les_anciens_conseils(): void
    {
        $anciens = $this->conseils(['raison' => 'Anciens conseils']);
        $this->equipement->forceFill(['conseils_ia' => $anciens, 'conseils_ia_le' => now()])->save();

        $reponses = ['pas du json', $this->conseils(['priorite' => 'urgente']), $this->conseils(['avant' => []]), $this->conseils(['pendant' => 'pas une liste'])];
        $sequence = Http::fakeSequence(self::URL);
        foreach ($reponses as $contenu) {
            $sequence->push($this->reponseIa($contenu));
        }

        foreach ($reponses as $contenu) {
            $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')
                ->assertSessionHas('error', 'Les conseils reçus sont incomplets. Cliquez à nouveau pour réessayer.');
        }

        Http::assertSentCount(count($reponses));
        $this->assertSame('Anciens conseils', $this->equipement->fresh()->conseils_ia['raison']);
    }

    public function test_le_html_de_l_ia_est_neutralise(): void
    {
        Http::fake([self::URL => Http::response($this->reponseIa($this->conseils(['raison' => '<script>alert(1)</script>Risque élevé'])))]);

        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia');

        $this->assertSame('alert(1)Risque élevé', $this->equipement->fresh()->conseils_ia['raison']);
        $this->actingAs($this->citoyen)->get($this->url())->assertDontSee('<script>alert(1)', false);
    }

    public function test_les_erreurs_de_l_api_donnent_un_message_clair(): void
    {
        Http::fakeSequence(self::URL)->push([], 429)->push([], 500)->pushFailedConnection();

        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertSessionHas('error', 'Le service de conseils reçoit trop de demandes. Réessayez dans une minute.');
        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertSessionHas('error', 'Le service de conseils est indisponible pour le moment. Réessayez plus tard.');
        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertSessionHas('error', 'Le service de conseils ne répond pas. Vérifiez votre connexion et réessayez.');

        $this->assertNull($this->equipement->fresh()->conseils_ia);
    }

    public function test_sans_cle_aucun_appel_n_est_fait(): void
    {
        config(['prevention.groq.cle' => null]);

        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertSessionHas('error', 'Le service de conseils n’est pas encore configuré.');

        Http::assertNothingSent();
    }

    public function test_cinq_generations_par_minute_maximum(): void
    {
        Http::fake([self::URL => Http::response($this->reponseIa($this->conseils()))]);

        foreach (range(1, 5) as $essai) {
            $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertSessionHas('success');
        }

        $this->actingAs($this->citoyen)->post($this->url().'/conseils-ia')->assertSessionHas('error');
        Http::assertSentCount(5);
    }

    public function test_un_citoyen_ne_genere_pas_pour_l_equipement_d_un_autre(): void
    {
        $voisin = User::factory()->create(['role' => 'user']);

        $this->actingAs($voisin)->post($this->url().'/conseils-ia')->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_modifier_l_equipement_retire_les_conseils_devenus_obsoletes(): void
    {
        $this->equipement->forceFill(['conseils_ia' => $this->conseils(), 'conseils_ia_le' => now()])->save();
        $donnees = ['nom' => 'Frigo de la cuisine', 'type' => 'refrigerateur', 'sensibilite' => 'les_deux', 'autonomie_heures' => 4, 'description' => 'Le frigo contient mon insuline.'];

        // Enregistrer sans rien changer conserve les conseils.
        $this->actingAs($this->citoyen)->put($this->url(), $donnees)->assertSessionHas('success', 'Équipement modifié.');
        $this->assertNotNull($this->equipement->fresh()->conseils_ia);

        $this->actingAs($this->citoyen)->put($this->url(), ['autonomie_heures' => 12] + $donnees)
            ->assertSessionHas('success', 'Équipement modifié. Générez de nouveaux conseils adaptés à ces changements.');
        $this->assertNull($this->equipement->fresh()->conseils_ia);
    }

    public function test_avertissement_medical_seulement_pour_un_appareil_medical(): void
    {
        $medical = $this->equipementDe($this->citoyen, ['nom' => 'Appareil à oxygène', 'type' => 'appareil_medical']);

        $this->actingAs($this->citoyen)->get('/user/mes-equipements/'.$medical->id)->assertSee('appelez le SAMU au 190');
        $this->actingAs($this->citoyen)->get($this->url())->assertDontSee('appelez le SAMU au 190');
    }
}
