<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CampaignMissionPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            ['/agent-campagne/missions', 'agent-campagne'],
            ['/user/campagnes', 'user'],
        ];
    }

    #[DataProvider('pages')]
    public function test_guests_cannot_view_campaign_interfaces(string $url, string $role): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    #[DataProvider('pages')]
    public function test_only_the_matching_role_can_view_its_interface(string $url, string $role): void
    {
        foreach (['user', 'agent-campagne', 'admin'] as $otherRole) {
            if ($otherRole !== $role) {
                $this->actingAs(User::factory()->create(['role' => $otherRole]))
                    ->get($url)->assertForbidden();
            }
        }
    }

    #[DataProvider('pages')]
    public function test_campaign_interfaces_render_without_database_writes(string $url, string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'name' => 'Utilisateur de test']);

        $response = $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertViewIs($role === 'agent-campagne' ? 'agent.campagnes.index' : 'campaigns.index')
            ->assertSee('data-user-id="'.$user->id.'"', false)
            ->assertSee($url);

        if ($role === 'agent-campagne') {
            $response->assertSee('Ajouter une mission')
                ->assertSee('data-editor-dialog', false)
                ->assertSee('data-participants-dialog', false)
                ->assertSee('data-delete-dialog', false);
        } else {
            $response->assertSee('ha-campaign-cards', false)
                ->assertDontSee('data-editor-dialog', false)
                ->assertDontSee('data-participants-dialog', false);
        }

        foreach (['campagnes', 'missions', 'participations'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }
}
