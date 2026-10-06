<?php

namespace Tests\Feature;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignalementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_signalements(): void
    {
        $this->get(route('user.signalements.carte'))->assertRedirect(route('login'));
    }

    public function test_only_citizens_can_access_signalements(): void
    {
        foreach (['agent-campagne', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('user.signalements.carte'))
                ->assertForbidden();
        }
    }

    public function test_citizen_can_create_a_signalement_with_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $resource = RessourceUtile::factory()->create();

        $response = $this->actingAs($user)->post(route('user.signalements.store'), [
            'ressource_utile_id' => $resource->id,
            'type_signalement' => 'incident',
            'type_incident' => 'point_eau_hors_service',
            'latitude' => 36.8124,
            'longitude' => 10.1763,
            'commentaire' => 'Le point d’eau ne fonctionne plus depuis ce matin.',
        ]);

        $response->assertRedirect();
        $signalement = Signalement::query()->firstOrFail();
        $this->assertSame($user->id, $signalement->user_id);
        $this->assertSame('en_attente', $signalement->statut_validation);
        $this->assertCount(0, $signalement->piecesJointes);
    }

    public function test_validation_requires_incident_details_and_tunisian_coordinates(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->from(route('user.signalements.create'))->post(route('user.signalements.store'), [
            'type_signalement' => 'incident',
            'latitude' => 29,
            'longitude' => 13,
            'commentaire' => 'court',
        ])->assertSessionHasErrors(['type_incident', 'latitude', 'longitude', 'commentaire']);
    }

    public function test_duplicate_signalement_is_rejected_for_five_minutes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Signalement::factory()->create([
            'user_id' => $user->id,
            'type_signalement' => 'ressource_ok',
            'latitude' => 36.8,
            'longitude' => 10.1,
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->post(route('user.signalements.store'), [
            'type_signalement' => 'ressource_ok',
            'latitude' => 36.8,
            'longitude' => 10.1,
        ])->assertSessionHasErrors('type_signalement');
    }

    public function test_piece_jointe_is_deleted_from_storage_with_signalement(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);
        Storage::disk('public')->put('signalements/preuve.jpg', 'contenu');
        $pieceJointe = $signalement->piecesJointes()->create([
            'chemin_fichier' => 'signalements/preuve.jpg',
            'nom_original' => 'preuve.jpg',
            'type_mime' => 'image/jpeg',
            'taille' => 7,
        ]);

        $this->actingAs($user)->delete(route('user.signalements.destroy', $signalement))->assertRedirect();
        Storage::disk('public')->assertMissing('signalements/preuve.jpg');
        $this->assertDatabaseMissing('pieces_jointes', ['id' => $pieceJointe->id]);
    }

    public function test_citizen_can_only_update_and_delete_own_pending_signalements(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $pending = Signalement::factory()->create(['user_id' => $owner->id]);
        $validated = Signalement::factory()->valide()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->get(route('user.signalements.show', $pending))->assertForbidden();
        $this->actingAs($owner)->put(route('user.signalements.update', $validated), [
            'type_signalement' => 'ressource_ok', 'latitude' => 36, 'longitude' => 10,
        ])->assertForbidden();
        $this->actingAs($owner)->delete(route('user.signalements.destroy', $validated))->assertForbidden();
        $this->actingAs($owner)->delete(route('user.signalements.destroy', $pending))->assertRedirect(route('user.signalements.index'));
        $this->assertDatabaseMissing('signalements', ['id' => $pending->id]);
    }

    public function test_citizen_can_update_an_own_pending_signalement(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put(route('user.signalements.update', $signalement), [
            'type_signalement' => 'incident',
            'type_incident' => 'autre',
            'latitude' => 36.2,
            'longitude' => 10.2,
            'commentaire' => 'La situation a évolué, merci de vérifier ce point.',
        ])->assertRedirect(route('user.signalements.show', $signalement));

        $this->assertDatabaseHas('signalements', [
            'id' => $signalement->id,
            'latitude' => 36.2,
            'longitude' => 10.2,
            'type_incident' => 'autre',
        ]);
    }

    public function test_citizen_can_update_using_the_html_form_method_override(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('user.signalements.update', $signalement), [
            '_method' => 'PUT',
            'type_signalement' => 'incident',
            'type_incident' => 'autre',
            'latitude' => 36.4,
            'longitude' => 10.3,
            'commentaire' => 'Modification envoyée depuis le formulaire citoyen.',
        ])->assertRedirect(route('user.signalements.show', $signalement));

        $this->assertDatabaseHas('signalements', ['id' => $signalement->id, 'latitude' => 36.4]);
    }

    public function test_edit_form_updates_a_signalement_that_has_an_existing_attachment(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);
        Storage::disk('public')->put('signalements/existing.jpg', 'image');
        $signalement->piecesJointes()->create([
            'chemin_fichier' => 'signalements/existing.jpg',
            'nom_original' => 'existing.jpg',
            'type_mime' => 'image/jpeg',
            'taille' => 5,
        ]);

        $this->actingAs($user)->post(route('user.signalements.update', $signalement), [
            '_method' => 'PUT',
            'type_signalement' => 'incident',
            'type_incident' => 'autre',
            'latitude' => 36.5,
            'longitude' => 10.4,
            'commentaire' => 'La modification fonctionne avec une image existante.',
        ])->assertRedirect(route('user.signalements.show', $signalement));

        $this->assertDatabaseHas('signalements', ['id' => $signalement->id, 'latitude' => 36.5]);
        Storage::disk('public')->assertExists('signalements/existing.jpg');
    }

    public function test_public_page_only_shows_validated_reports_from_other_citizens(): void
    {
        $viewer = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $visible = Signalement::factory()->valide()->create(['user_id' => $other->id]);
        $own = Signalement::factory()->valide()->create(['user_id' => $viewer->id]);

        $response = $this->actingAs($viewer)->get(route('user.signalements.public.index'));

        $response->assertOk()->assertSee($visible->commentaire)->assertDontSee($own->commentaire);
    }
}
