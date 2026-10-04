<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee($user->name);
        $this->actingAs($admin)->get('/admin/users/'.$user->id)->assertOk()->assertSee($user->email);
        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/role', ['role' => 'agent-campagne'])
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'agent-campagne']);

        $this->actingAs($admin)->put('/admin/users/'.$user->id, [
            'name' => 'Utilisateur modifié',
            'email' => $user->email,
            'governorate' => 'Tunis',
            'role' => 'agent-campagne',
        ])->assertRedirect('/admin/users/'.$user->id);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Utilisateur modifié', 'governorate' => 'Tunis']);

        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/archive')->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'archived' => true]);
    }

    public function test_only_admins_can_access_user_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->patch('/admin/users/'.$user->id.'/archive')->assertForbidden();
        auth()->logout();
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_admin_cannot_change_or_archive_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/role', ['role' => 'user'])
            ->assertRedirect()->assertSessionHas('error');
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/archive')->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'archived' => false]);
    }
}
