<?php

namespace Tests\Feature;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RessourcesUtilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_red_and_green_resources_and_supports_filters(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $red = RessourceUtile::factory()->create(['gouvernorat' => 'Tunis']);
        $green = RessourceUtile::factory()->create(['gouvernorat' => 'Sfax']);
        Signalement::factory()->valide()->create([
            'user_id' => $user->id,
            'ressource_utile_id' => $red->id,
            'type_signalement' => 'incident',
        ]);

        $response = $this->actingAs($user)->getJson(route('user.signalements.api.ressources', ['etat' => 'rouge', 'gouvernorat' => 'Tunis']));

        $response->assertOk()->assertJsonPath('data.0.id', $red->id)->assertJsonPath('data.0.etat', 'rouge');
        $this->assertSame('vert', $green->fresh()->etat);
    }

    public function test_resource_page_is_citizen_only_and_shows_valid_history(): void
    {
        $resource = RessourceUtile::factory()->create();
        $user = User::factory()->create(['role' => 'user']);
        Signalement::factory()->valide()->create(['user_id' => $user->id, 'ressource_utile_id' => $resource->id]);

        $this->actingAs($user)->get(route('user.ressources.show', $resource))->assertOk()->assertSee($resource->nom);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('user.ressources.show', $resource))->assertForbidden();
    }
}
