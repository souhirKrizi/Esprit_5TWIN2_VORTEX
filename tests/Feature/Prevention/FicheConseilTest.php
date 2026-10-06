<?php

namespace Tests\Feature\Prevention;

use App\Models\FicheConseil;
use App\Models\GesteCle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheConseilTest extends TestCase
{
    use RefreshDatabase;

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'titre' => 'Bien s’hydrater pendant la canicule',
            'resume' => 'Buvez souvent, même sans avoir soif.',
            'contenu' => str_repeat('Quand il fait très chaud, le corps perd beaucoup d’eau. ', 2),
            'thematique' => 'hydratation',
            'niveau_alerte' => 'jaune',
            'type_equipement' => '',
            'statut' => 'publiee',
        ], $surcharge);
    }

    private function fiche(array $attributs = []): FicheConseil
    {
        return FicheConseil::create(array_merge($this->donnees(), ['type_equipement' => null], $attributs));
    }

    // ----- Administration : CRUD des fiches -----

    public function test_admin_ajoute_voit_modifie_et_supprime_une_fiche(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/prevention/fiches')->assertOk()->assertSee('Fiches conseils');
        $this->actingAs($admin)->get('/admin/prevention/fiches/create')->assertOk()->assertSee('Ajouter une fiche');

        $reponse = $this->actingAs($admin)->post('/admin/prevention/fiches', $this->donnees());
        $fiche = FicheConseil::firstOrFail();
        $reponse->assertRedirect('/admin/prevention/fiches/'.$fiche->id)->assertSessionHas('success');

        $this->actingAs($admin)->get('/admin/prevention/fiches/'.$fiche->id)->assertOk()->assertSee($fiche->titre);
        $this->actingAs($admin)->get('/admin/prevention/fiches/'.$fiche->id.'/edit')->assertOk();

        $this->actingAs($admin)->put('/admin/prevention/fiches/'.$fiche->id, $this->donnees(['titre' => 'Boire de l’eau quand il fait chaud']))
            ->assertRedirect('/admin/prevention/fiches/'.$fiche->id);
        $this->assertSame('Boire de l’eau quand il fait chaud', $fiche->fresh()->titre);

        $this->actingAs($admin)->delete('/admin/prevention/fiches/'.$fiche->id)->assertRedirect('/admin/prevention/fiches');
        $this->assertDatabaseCount('fiches_conseils', 0);
    }

    public function test_controle_de_saisie_de_la_fiche(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/prevention/fiches', [])
            ->assertSessionHasErrors(['titre', 'resume', 'contenu', 'thematique', 'niveau_alerte', 'statut']);

        $this->actingAs($admin)->post('/admin/prevention/fiches', $this->donnees([
            'titre' => 'Eau',
            'resume' => 'Court',
            'contenu' => 'Court',
            'thematique' => 'inconnue',
            'niveau_alerte' => 'violet',
            'statut' => 'archivee',
        ]))->assertSessionHasErrors(['titre', 'resume', 'contenu', 'thematique', 'niveau_alerte', 'statut']);

        $this->actingAs($admin)->post('/admin/prevention/fiches', $this->donnees(['titre' => '']))
            ->assertSessionHasErrors(['titre' => 'Le champ « titre » est obligatoire.']);

        $this->assertDatabaseCount('fiches_conseils', 0);
    }

    public function test_titre_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fiche = $this->fiche();

        $this->actingAs($admin)->post('/admin/prevention/fiches', $this->donnees())
            ->assertSessionHasErrors(['titre' => 'Une fiche avec ce titre existe déjà.']);

        // En modification, la fiche peut garder son propre titre.
        $this->actingAs($admin)->put('/admin/prevention/fiches/'.$fiche->id, $this->donnees())->assertSessionHasNoErrors();
    }

    public function test_equipement_obligatoire_pour_la_thematique_equipements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/prevention/fiches', $this->donnees(['thematique' => 'equipements']))
            ->assertSessionHasErrors('type_equipement');

        $this->actingAs($admin)->post('/admin/prevention/fiches', $this->donnees(['thematique' => 'equipements', 'type_equipement' => 'refrigerateur']))
            ->assertSessionHasNoErrors();
    }

    public function test_seul_l_admin_gere_les_fiches(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $fiche = $this->fiche();

        $this->actingAs($citoyen)->get('/admin/prevention/fiches')->assertForbidden();
        $this->actingAs($citoyen)->post('/admin/prevention/fiches', $this->donnees(['titre' => 'Une autre fiche']))->assertForbidden();
        $this->actingAs($citoyen)->delete('/admin/prevention/fiches/'.$fiche->id)->assertForbidden();
        $this->assertDatabaseCount('fiches_conseils', 1);
    }

    // ----- Administration : CRUD des gestes -----

    public function test_admin_ajoute_modifie_et_supprime_un_geste(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fiche = $this->fiche();

        $this->actingAs($admin)->post('/admin/prevention/fiches/'.$fiche->id.'/gestes', ['intitule' => 'Mettre de l’eau au frais', 'moment' => 'avant', 'ordre' => 1])
            ->assertRedirect('/admin/prevention/fiches/'.$fiche->id);
        $geste = GesteCle::firstOrFail();
        $this->assertSame($fiche->id, $geste->fiche_conseil_id);

        $this->actingAs($admin)->get('/admin/prevention/gestes/'.$geste->id.'/edit')->assertOk();
        $this->actingAs($admin)->put('/admin/prevention/gestes/'.$geste->id, ['intitule' => 'Boire un verre par heure', 'moment' => 'pendant', 'ordre' => 2])
            ->assertRedirect('/admin/prevention/fiches/'.$fiche->id);
        $this->assertSame('pendant', $geste->fresh()->moment);

        $this->actingAs($admin)->delete('/admin/prevention/gestes/'.$geste->id)->assertRedirect('/admin/prevention/fiches/'.$fiche->id);
        $this->assertDatabaseCount('gestes_cles', 0);
    }

    public function test_controle_de_saisie_du_geste(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fiche = $this->fiche();
        $url = '/admin/prevention/fiches/'.$fiche->id.'/gestes';

        $this->actingAs($admin)->post($url, [])->assertSessionHasErrors(['intitule', 'moment', 'ordre']);
        $this->actingAs($admin)->post($url, ['intitule' => 'Eau', 'moment' => 'demain', 'ordre' => 50])
            ->assertSessionHasErrors(['intitule', 'moment', 'ordre']);

        $this->actingAs($admin)->post($url, ['intitule' => 'Garder le frigo fermé', 'moment' => 'pendant', 'ordre' => 1])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['intitule' => 'Garder le frigo fermé', 'moment' => 'pendant', 'ordre' => 2])
            ->assertSessionHasErrors(['intitule' => 'Ce geste existe déjà dans cette fiche.']);

        $this->assertDatabaseCount('gestes_cles', 1);
    }

    public function test_supprimer_une_fiche_supprime_ses_gestes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fiche = $this->fiche();
        $fiche->gestes()->create(['intitule' => 'Préparer une lampe torche', 'moment' => 'avant', 'ordre' => 1]);

        $this->actingAs($admin)->delete('/admin/prevention/fiches/'.$fiche->id);

        $this->assertDatabaseCount('gestes_cles', 0);
    }

    // ----- Côté citoyen : consultation -----

    public function test_le_citoyen_ne_voit_que_les_fiches_publiees(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $publiee = $this->fiche();
        $brouillon = $this->fiche(['titre' => 'Fiche pas encore prête', 'statut' => 'brouillon']);

        $this->actingAs($citoyen)->get('/conseils')->assertOk()->assertSee($publiee->titre)->assertDontSee('Fiche pas encore prête');
        $this->actingAs($citoyen)->get('/conseils/'.$brouillon->id)->assertNotFound();
    }

    public function test_recherche_et_filtre_par_thematique(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $this->fiche();
        $this->fiche(['titre' => 'Se préparer à une coupure', 'thematique' => 'coupure_courant']);

        $this->actingAs($citoyen)->get('/conseils?thematique=coupure_courant')->assertSee('Se préparer à une coupure')->assertDontSee('Bien s’hydrater');
        $this->actingAs($citoyen)->get('/conseils?recherche=hydrater')->assertSee('Bien s’hydrater')->assertDontSee('Se préparer à une coupure');
    }

    public function test_detail_d_une_fiche_avec_ses_gestes(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $fiche = $this->fiche();
        $fiche->gestes()->create(['intitule' => 'Boire un verre d’eau par heure', 'moment' => 'pendant', 'ordre' => 1]);

        $this->actingAs($citoyen)->get('/conseils/'.$fiche->id)->assertOk()
            ->assertSee($fiche->titre)
            ->assertSee('Les gestes à faire')
            ->assertSee('Boire un verre d’eau par heure');
    }

    public function test_connexion_obligatoire(): void
    {
        $this->get('/conseils')->assertRedirect('/login');
        $this->get('/admin/prevention/fiches')->assertRedirect('/login');
    }
}
