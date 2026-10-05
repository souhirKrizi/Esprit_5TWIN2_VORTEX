<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminCampaignPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_must_sign_in(): void
    {
        $this->get('/admin/campagne')->assertRedirect('/login');
    }

    public static function nonAdminRoles(): array
    {
        return [['user'], ['agent-campagne']];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_other_roles_cannot_access_the_admin_campaign_page(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get('/admin/campagne')->assertForbidden();
    }

    public function test_admin_can_view_the_frontend_without_creating_campaign_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/campagne')
            ->assertOk()
            ->assertViewIs('admin.campagnes.index')
            ->assertSee('Campagnes de prévention')
            ->assertSee('Ajouter une campagne')
            ->assertSee('Supprimer cette campagne ?')
            ->assertSee('Choisir un agent de campagne')
            ->assertSee('data-campaign-create', false)
            ->assertSee('data-campaign-delete', false);

        $this->assertDatabaseCount('campagnes', 0);
    }
}
