<?php

namespace Tests\Feature\Prevention;

use App\Models\EquipementSensible;
use App\Models\FicheConseil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipementSensibleTest extends TestCase
{
    use RefreshDatabase;

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Frigo de la cuisine',
            'type' => 'refrigerateur',
            'sensibilite' => 'les_deux',
            'autonomie_heures' => 4,
        ], $surcharge);
    }

    private function equipementDe(User $user, array $attributs = []): EquipementSensible
    {
        $equipement = new EquipementSensible(array_merge($this->donnees(), $attributs));
        $equipement->user_id = $user->id;
        $equipement->save();

        return $equipement;
    }

    public function test_le_citoyen_ajoute_voit_modifie_et_supprime_un_equipement(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        $this->actingAs($citoyen)->get('/mes-equipements')->assertOk()->assertSee('Vous n’avez encore ajouté aucun équipement.');
        $this->actingAs($citoyen)->get('/mes-equipements/create')->assertOk();

        $reponse = $this->actingAs($citoyen)->post('/mes-equipements', $this->donnees());
        $equipement = EquipementSensible::firstOrFail();
        $reponse->assertRedirect('/mes-equipements/'.$equipement->id);
        $this->assertSame($citoyen->id, $equipement->user_id);

        $this->actingAs($citoyen)->get('/mes-equipements')->assertSee('Frigo de la cuisine');
        $this->actingAs($citoyen)->get('/mes-equipements/'.$equipement->id)->assertOk();
        $this->actingAs($citoyen)->get('/mes-equipements/'.$equipement->id.'/edit')->assertOk();

        $this->actingAs($citoyen)->put('/mes-equipements/'.$equipement->id, $this->donnees(['autonomie_heures' => '']))
            ->assertRedirect('/mes-equipements/'.$equipement->id);
        $this->assertNull($equipement->fresh()->autonomie_heures);

        $this->actingAs($citoyen)->delete('/mes-equipements/'.$equipement->id)->assertRedirect('/mes-equipements');
        $this->assertDatabaseCount('equipements_sensibles', 0);
    }

    public function test_controle_de_saisie_de_l_equipement(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        $this->actingAs($citoyen)->post('/mes-equipements', [])->assertSessionHasErrors(['nom', 'type', 'sensibilite']);

        $this->actingAs($citoyen)->post('/mes-equipements', $this->donnees(['nom' => 'A', 'type' => 'four', 'sensibilite' => 'froid', 'autonomie_heures' => 100]))
            ->assertSessionHasErrors(['nom', 'type', 'sensibilite', 'autonomie_heures']);

        $this->actingAs($citoyen)->post('/mes-equipements', $this->donnees(['autonomie_heures' => 'beaucoup']))
            ->assertSessionHasErrors(['autonomie_heures' => 'Le champ « autonomie » doit être un nombre entier.']);

        $this->actingAs($citoyen)->post('/mes-equipements', $this->donnees(['autonomie_heures' => -1]))
            ->assertSessionHasErrors(['autonomie_heures' => 'L’autonomie doit être comprise entre 0 et 72 heures.']);

        $this->assertDatabaseCount('equipements_sensibles', 0);
    }

    public function test_nom_unique_pour_un_meme_citoyen(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $voisin = User::factory()->create(['role' => 'user']);
        $this->equipementDe($voisin);

        // Le voisin a le même nom : autorisé.
        $this->actingAs($citoyen)->post('/mes-equipements', $this->donnees())->assertSessionHasNoErrors();
        // Deux fois le même nom pour le même citoyen : refusé.
        $this->actingAs($citoyen)->post('/mes-equipements', $this->donnees())
            ->assertSessionHasErrors(['nom' => 'Vous avez déjà un équipement avec ce nom.']);
    }

    public function test_un_citoyen_ne_touche_pas_aux_equipements_des_autres(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $voisin = User::factory()->create(['role' => 'user']);
        $equipement = $this->equipementDe($voisin, ['nom' => 'Appareil à oxygène du voisin']);

        $this->actingAs($citoyen)->get('/mes-equipements')->assertDontSee('Appareil à oxygène du voisin');
        $this->actingAs($citoyen)->get('/mes-equipements/'.$equipement->id)->assertForbidden();
        $this->actingAs($citoyen)->get('/mes-equipements/'.$equipement->id.'/edit')->assertForbidden();
        $this->actingAs($citoyen)->put('/mes-equipements/'.$equipement->id, $this->donnees())->assertForbidden();
        $this->actingAs($citoyen)->delete('/mes-equipements/'.$equipement->id)->assertForbidden();
        $this->assertDatabaseCount('equipements_sensibles', 1);
    }

    public function test_valeur_ajoutee_les_conseils_de_l_equipement_sont_affiches(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $equipement = $this->equipementDe($citoyen);
        $base = [
            'resume' => 'Garder le froid le plus longtemps possible.',
            'contenu' => str_repeat('Un frigo fermé garde le froid environ quatre heures. ', 2),
            'thematique' => 'equipements',
            'niveau_alerte' => 'jaune',
        ];
        FicheConseil::create($base + ['titre' => 'Protéger le frigo', 'type_equipement' => 'refrigerateur', 'statut' => 'publiee']);
        FicheConseil::create($base + ['titre' => 'Frigo en brouillon', 'type_equipement' => 'refrigerateur', 'statut' => 'brouillon']);
        FicheConseil::create($base + ['titre' => 'Appareil médical', 'type_equipement' => 'appareil_medical', 'statut' => 'publiee']);

        $this->actingAs($citoyen)->get('/mes-equipements')->assertSee('1 conseil');
        $this->actingAs($citoyen)->get('/mes-equipements/'.$equipement->id)
            ->assertSee('Protéger le frigo')
            ->assertDontSee('Frigo en brouillon')
            ->assertDontSee('Appareil médical');
    }
}
