<?php

namespace Tests\Feature;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSignalementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_signalement_moderation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.signalements.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.signalements.index'))
            ->assertOk();
    }

    public function test_admin_can_validate_a_pending_signalement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $signalement = Signalement::factory()->create(['statut_validation' => Signalement::STATUT_EN_ATTENTE]);

        $this->actingAs($admin)
            ->patch(route('admin.signalements.validate', $signalement))
            ->assertRedirect();

        $this->assertDatabaseHas('signalements', [
            'id' => $signalement->id,
            'statut_validation' => Signalement::STATUT_VALIDE,
            'motif_rejet' => null,
        ]);
    }

    public function test_admin_rejection_requires_a_reason_and_stores_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $signalement = Signalement::factory()->create(['statut_validation' => Signalement::STATUT_EN_ATTENTE]);

        $this->actingAs($admin)
            ->patch(route('admin.signalements.reject', $signalement), ['motif_rejet' => 'court'])
            ->assertSessionHasErrors('motif_rejet');

        $this->actingAs($admin)
            ->post(route('admin.signalements.reject', $signalement), ['motif_rejet' => 'Les informations doivent être vérifiées sur place.'])
            ->assertRedirect();

        $this->assertDatabaseHas('signalements', [
            'id' => $signalement->id,
            'statut_validation' => Signalement::STATUT_REJETE,
            'motif_rejet' => 'Les informations doivent être vérifiées sur place.',
        ]);
    }

    public function test_admin_map_api_returns_red_and_green_resources(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $red = RessourceUtile::factory()->create();
        $green = RessourceUtile::factory()->create();
        Signalement::factory()->valide()->create([
            'ressource_utile_id' => $red->id,
            'type_signalement' => Signalement::TYPE_INCIDENT,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.signalements.api.ressources'));

        $response->assertOk()->assertJsonPath('compteurs.incidents_actifs', 1)->assertJsonPath('signalements.0.id', Signalement::query()->where('ressource_utile_id', $red->id)->value('id'));
        $this->assertSame('rouge', collect($response->json('data'))->firstWhere('id', $red->id)['etat']);
        $this->assertSame('vert', collect($response->json('data'))->firstWhere('id', $green->id)['etat']);
    }
}
