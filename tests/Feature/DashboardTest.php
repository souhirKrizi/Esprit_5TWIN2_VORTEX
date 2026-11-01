<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_account_totals_roles_and_zero_filled_months(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(12, 0));
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'agent-campagne', 'archived' => true, 'created_at' => now()->subMonths(2)]);
        User::factory()->create(['role' => 'user', 'created_at' => now()->subYear()]);

        $this->actingAs($admin)->get($admin->spaceUrl())->assertOk()
            ->assertSee('Répartition par rôle')
            ->assertViewHas('dashboard', function ($data) {
                $this->assertSame([3, 2, 1, 1], array_values($data['metrics']));
                $this->assertSame([1, 1, 1], array_column($data['charts'][0]['values'], 'value'));
                $this->assertSame([0, 0, 0, 1, 0, 1], array_column($data['charts'][1]['values'], 'value'));

                return true;
            });
    }

    public function test_agent_statistics_only_include_owned_campaigns_and_their_participants(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $owned = Campagne::factory()->create(['organisateur_id' => $agent->id, 'statut' => 'en_cours']);
        $mission = Mission::factory()->create(['campagne_id' => $owned->id]);
        $foreignMission = Mission::factory()->create();
        $citizen = User::factory()->create();
        foreach ([$mission, $foreignMission] as $item) {
            Participation::create(['mission_id' => $item->id, 'user_id' => $citizen->id, 'statut' => 'acceptee']);
        }

        $this->actingAs($agent)->get($agent->spaceUrl())->assertOk()
            ->assertViewHas('dashboard', function ($data) {
                $this->assertSame([1, 1, 1, 1], array_values($data['metrics']));
                $this->assertSame([0, 0, 1, 0, 0], array_column($data['charts'][0]['values'], 'value'));
                $this->assertSame([0, 1, 0, 0], array_column($data['charts'][2]['values'], 'value'));

                return true;
            });
    }

    public function test_citizen_sees_only_personal_registrations_and_active_registered_missions(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $finished = Mission::factory()->create(['statut' => 'terminee']);
        $cancelledRegistration = Mission::factory()->create(['statut' => 'ouverte']);
        $foreignMission = Mission::factory()->create(['statut' => 'terminee']);
        Participation::create(['user_id' => $user->id, 'mission_id' => $finished->id, 'statut' => 'acceptee']);
        Participation::create(['user_id' => $user->id, 'mission_id' => $cancelledRegistration->id, 'statut' => 'annulee']);
        Participation::create(['user_id' => $other->id, 'mission_id' => $foreignMission->id, 'statut' => 'acceptee']);

        $this->actingAs($user)->get($user->spaceUrl())->assertOk()
            ->assertSee('Mon tableau de bord')->assertSee('dashboard.css')
            ->assertViewHas('dashboard', function ($data) {
                $this->assertSame([2, 1, 0, 1], array_values($data['metrics']));
                $this->assertSame([0, 1, 0, 1], array_column($data['charts'][0]['values'], 'value'));
                $this->assertSame([0, 0, 0, 1, 0], array_column($data['charts'][2]['values'], 'value'));

                return true;
            });
    }

    public function test_empty_dashboards_render_without_division_errors(): void
    {
        foreach (['admin', 'agent-campagne', 'user'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $response = $this->actingAs($user)->get($user->spaceUrl())->assertOk();
            if ($role !== 'admin') {
                $response->assertSee('Aucune donnée pour le moment');
            }
        }
    }
}
