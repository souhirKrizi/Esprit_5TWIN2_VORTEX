<?php

namespace Tests\Feature\Prevention;

use App\Models\FicheConseil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlerteEnCoursTest extends TestCase
{
    use RefreshDatabase;

    private function fiche(string $titre, string $niveau, string $statut = 'publiee'): FicheConseil
    {
        return FicheConseil::create([
            'titre' => $titre,
            'resume' => 'Un résumé du conseil de prévention.',
            'contenu' => 'Le contenu complet du conseil de prévention.',
            'thematique' => 'hydratation',
            'niveau_alerte' => $niveau,
            'statut' => $statut,
        ]);
    }

    public function test_l_admin_choisit_puis_retire_l_alerte_en_cours(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/conseils/alerte-en-cours', ['niveau' => 'orange'])
            ->assertRedirect('/admin/conseils')->assertSessionHas('success');
        $this->assertSame('orange', FicheConseil::niveauAlerteEnCours());
        $this->assertDatabaseCount('alerte_en_cours', 1);

        $this->actingAs($admin)->get('/admin/conseils')->assertViewHas('alerteEnCours', 'orange');

        $this->actingAs($admin)->put('/admin/conseils/alerte-en-cours', ['niveau' => ''])->assertSessionHasNoErrors();
        $this->assertNull(FicheConseil::niveauAlerteEnCours());
        $this->assertDatabaseCount('alerte_en_cours', 1);
    }

    public function test_le_niveau_est_controle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/conseils/alerte-en-cours', ['niveau' => 'violet'])
            ->assertSessionHasErrors(['niveau' => 'Choisissez une valeur valide pour « alerte en cours ».']);
        $this->assertNull(FicheConseil::niveauAlerteEnCours());
    }

    public function test_seul_l_admin_change_l_alerte(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        $this->actingAs($citoyen)->put('/admin/conseils/alerte-en-cours', ['niveau' => 'rouge'])->assertForbidden();
        $this->assertNull(FicheConseil::niveauAlerteEnCours());
    }

    public function test_sans_alerte_rien_n_est_mis_en_avant(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $this->fiche('Conseil orange', 'orange');

        $this->actingAs($citoyen)->get('/user/conseils')->assertOk()->assertDontSee('À lire en priorité');
    }

    public function test_les_conseils_du_niveau_en_cours_passent_en_premier(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $this->fiche('Conseil vert', 'vert');
        $this->fiche('Conseil jaune', 'jaune');
        $this->fiche('Conseil orange', 'orange');
        $this->fiche('Conseil rouge', 'rouge');
        $this->fiche('Orange en brouillon', 'orange', 'brouillon');
        FicheConseil::definirNiveauAlerteEnCours('orange');

        $this->actingAs($citoyen)->get('/user/conseils')->assertOk()
            ->assertSee('Alerte orange en cours')
            ->assertViewHas('conseilsAlerte', fn ($conseils) => $conseils->pluck('titre')->all() === ['Conseil orange', 'Conseil jaune', 'Conseil vert']);
    }

    public function test_trois_conseils_maximum(): void
    {
        foreach (range(1, 5) as $numero) {
            $this->fiche("Conseil rouge {$numero}", 'rouge');
        }

        $this->assertCount(3, FicheConseil::conseilsPourAlerte('rouge'));
    }

    public function test_message_si_aucun_conseil_pour_ce_niveau(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $this->fiche('Conseil rouge', 'rouge');
        FicheConseil::definirNiveauAlerteEnCours('vert');

        $this->actingAs($citoyen)->get('/user/conseils')->assertSee('Aucun conseil n’est encore disponible pour ce niveau.');
    }

    public function test_le_bloc_est_masque_pendant_une_recherche(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $this->fiche('Conseil orange', 'orange');
        FicheConseil::definirNiveauAlerteEnCours('orange');

        $this->actingAs($citoyen)->get('/user/conseils?recherche=orange')->assertOk()->assertDontSee('À lire en priorité');
    }
}
