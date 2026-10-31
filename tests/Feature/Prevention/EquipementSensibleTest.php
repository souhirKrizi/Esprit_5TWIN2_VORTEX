<?php

namespace Tests\Feature\Prevention;

use App\Models\EquipementSensible;
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

        $this->actingAs($citoyen)->get('/user/mes-equipements')->assertOk()->assertSee('Vous n’avez encore ajouté aucun équipement.');
        $this->actingAs($citoyen)->get('/user/mes-equipements/create')->assertOk();

        $reponse = $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees());
        $equipement = EquipementSensible::firstOrFail();
        $reponse->assertRedirect('/user/mes-equipements/'.$equipement->id);
        $this->assertSame($citoyen->id, $equipement->user_id);

        $this->actingAs($citoyen)->get('/user/mes-equipements')->assertSee('Frigo de la cuisine');
        $this->actingAs($citoyen)->get('/user/mes-equipements/'.$equipement->id)->assertOk();
        $this->actingAs($citoyen)->get('/user/mes-equipements/'.$equipement->id.'/edit')->assertOk();

        $this->actingAs($citoyen)->put('/user/mes-equipements/'.$equipement->id, $this->donnees(['autonomie_heures' => '']))
            ->assertRedirect('/user/mes-equipements/'.$equipement->id);
        $this->assertNull($equipement->fresh()->autonomie_heures);

        $this->actingAs($citoyen)->delete('/user/mes-equipements/'.$equipement->id)->assertRedirect('/user/mes-equipements');
        $this->assertDatabaseCount('equipements_sensibles', 0);
    }

    public function test_controle_de_saisie_de_l_equipement(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        $this->actingAs($citoyen)->post('/user/mes-equipements', [])->assertSessionHasErrors(['nom', 'type', 'sensibilite']);

        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees(['nom' => 'A', 'type' => 'four', 'sensibilite' => 'froid', 'autonomie_heures' => 100]))
            ->assertSessionHasErrors(['nom', 'type', 'sensibilite', 'autonomie_heures']);

        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees(['autonomie_heures' => 'beaucoup']))
            ->assertSessionHasErrors(['autonomie_heures' => 'Le champ « autonomie » doit être un nombre entier.']);

        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees(['autonomie_heures' => -1]))
            ->assertSessionHasErrors(['autonomie_heures' => 'L’autonomie doit être comprise entre 0 et 72 heures.']);

        $this->assertDatabaseCount('equipements_sensibles', 0);
    }

    public function test_description_du_probleme(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        // La description est facultative mais, si elle est remplie, elle doit faire entre 10 et 500 caractères.
        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees(['description' => 'Court']))
            ->assertSessionHasErrors(['description' => 'Le champ « description » doit contenir au moins 10 caractères.']);
        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees(['description' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('description');
        $this->assertDatabaseCount('equipements_sensibles', 0);

        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees(['description' => 'Le frigo s’arrête à chaque coupure de courant.']))
            ->assertSessionHasNoErrors();
        $equipement = EquipementSensible::firstOrFail();
        $this->actingAs($citoyen)->get('/user/mes-equipements/'.$equipement->id)->assertSee('Le frigo s’arrête à chaque coupure de courant.');

        $this->actingAs($citoyen)->put('/user/mes-equipements/'.$equipement->id, $this->donnees(['description' => '']))->assertSessionHasNoErrors();
        $this->assertNull($equipement->fresh()->description);
    }

    public function test_nom_unique_pour_un_meme_citoyen(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $voisin = User::factory()->create(['role' => 'user']);
        $this->equipementDe($voisin);

        // Le voisin a le même nom : autorisé.
        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees())->assertSessionHasNoErrors();
        // Deux fois le même nom pour le même citoyen : refusé.
        $this->actingAs($citoyen)->post('/user/mes-equipements', $this->donnees())
            ->assertSessionHasErrors(['nom' => 'Vous avez déjà un équipement avec ce nom.']);
    }

    public function test_un_citoyen_ne_touche_pas_aux_equipements_des_autres(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $voisin = User::factory()->create(['role' => 'user']);
        $equipement = $this->equipementDe($voisin, ['nom' => 'Appareil à oxygène du voisin']);

        $this->actingAs($citoyen)->get('/user/mes-equipements')->assertDontSee('Appareil à oxygène du voisin');
        $this->actingAs($citoyen)->get('/user/mes-equipements/'.$equipement->id)->assertForbidden();
        $this->actingAs($citoyen)->get('/user/mes-equipements/'.$equipement->id.'/edit')->assertForbidden();
        $this->actingAs($citoyen)->put('/user/mes-equipements/'.$equipement->id, $this->donnees())->assertForbidden();
        $this->actingAs($citoyen)->delete('/user/mes-equipements/'.$equipement->id)->assertForbidden();
        $this->assertDatabaseCount('equipements_sensibles', 1);
    }
}
