<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SignInTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [['user'], ['agent-campagne'], ['admin']];
    }

    public function test_registration_keeps_the_user_signed_in_and_redirects_to_their_user_space(): void
    {
        $this->post('/register', [
            'name' => 'Nouvelle Citoyenne',
            'email' => 'nouvelle@example.com',
            'governorate' => 'Tunis',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'terms' => '1',
        ])->assertRedirect('/user/1');

        $user = User::where('email', 'nouvelle@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertAuthenticatedAs($user);
        $this->get('/user/'.$user->id)->assertOk()->assertSee($user->name);
    }

    public function test_json_registration_returns_the_user_space_destination(): void
    {
        $this->postJson('/register', [
            'name' => 'Nouvelle Citoyenne',
            'email' => 'nouvelle-json@example.com',
            'governorate' => 'Tunis',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'terms' => '1',
        ])->assertOk()->assertJsonPath('redirect', route('spaces.user', ['user' => 1]));

        $this->assertAuthenticatedAs(User::where('email', 'nouvelle-json@example.com')->firstOrFail());
    }

    #[DataProvider('roles')]
    public function test_authenticated_users_are_sent_to_their_space_from_auth_pages(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        $this->get('/')->assertOk()->assertSee('href="'.$user->spaceUrl().'"', false);
        foreach (['/signin', '/signup', '/login', '/register'] as $path) {
            $this->get($path)->assertRedirect($user->spaceUrl());
        }
        $this->postJson('/login', [])->assertRedirect($user->spaceUrl());
        $this->assertAuthenticatedAs($user);
    }

    public function test_guests_can_open_auth_pages_from_the_homepage(): void
    {
        $this->get('/')->assertOk()->assertSee('href="'.route('signin').'"', false);
        $this->get('/signin')->assertOk();
        $this->get('/signup')->assertOk();
    }

    public function test_registration_keeps_the_new_user_authenticated_and_redirects_to_their_space(): void
    {
        $this->withSession(['url.intended' => '/admin'])->post('/register', [
            'name' => 'Nouveau citoyen',
            'email' => 'citoyen@example.com',
            'governorate' => 'Tunis',
            'password' => 'Citoyen123',
            'password_confirmation' => 'Citoyen123',
            'terms' => '1',
            'role' => 'admin',
        ])->assertRedirect('/user/'.User::where('email', 'citoyen@example.com')->firstOrFail()->id);

        $user = User::where('email', 'citoyen@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertAuthenticatedAs($user);
        $this->get($user->spaceUrl())->assertOk();
        $this->get('/signin')->assertRedirect($user->spaceUrl());
    }

    public function test_json_registration_returns_the_space_and_keeps_the_session(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Citoyen JSON',
            'email' => 'json@example.com',
            'governorate' => 'Tunis',
            'password' => 'Citoyen123',
            'password_confirmation' => 'Citoyen123',
            'terms' => '1',
        ]);

        $user = User::where('email', 'json@example.com')->firstOrFail();
        $response->assertCreated()->assertJsonPath('redirect', $user->spaceUrl());
        $this->assertAuthenticatedAs($user);
        $this->get($user->spaceUrl())->assertOk();
    }

    #[DataProvider('roles')]
    public function test_login_redirects_to_the_stored_role_and_user_id(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->withSession(['url.intended' => '/admin'])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'admin',
            'id' => 999,
        ])->assertRedirect('/'.$role.'/'.$user->id);

        $this->assertAuthenticatedAs($user);
        $this->get('/'.$role.'/'.$user->id)->assertOk()->assertSee('Déconnexion')->assertSee($user->name)
            ->assertSee(route('spaces.'.$role, ['user' => $user->id]));
    }

    #[DataProvider('roles')]
    public function test_json_login_returns_the_role_destination(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('redirect', route('spaces.'.$role, ['user' => $user->id]));
        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('roles')]
    public function test_personal_spaces_require_authentication(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->get('/'.$role.'/'.$user->id)->assertRedirect('/login');
        $this->get('/'.$role)->assertRedirect('/login');
    }

    #[DataProvider('roles')]
    public function test_users_cannot_access_another_accounts_space_or_another_role(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $other = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get('/'.$role.'/'.$other->id)->assertForbidden();

        foreach (['user', 'agent-campagne', 'admin'] as $otherRole) {
            if ($otherRole !== $role) {
                $this->get('/'.$otherRole.'/'.$user->id)->assertForbidden();
            }
        }
    }

    #[DataProvider('roles')]
    public function test_legacy_space_links_redirect_to_the_authenticated_users_space(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        foreach (['user', 'agent-campagne', 'admin'] as $space) {
            $this->actingAs($user)->get('/'.$space)->assertRedirect('/'.$role.'/'.$user->id);
        }
    }

    public function test_invalid_credentials_are_rejected_and_render_the_popup(): void
    {
        $user = User::factory()->create();
        $this->from('/signin')->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertRedirect('/signin')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->get('/signin')->assertOk()->assertSee('data-show-error', false)
            ->assertSee('Adresse e-mail ou mot de passe incorrect.');
    }

    public function test_json_invalid_credentials_and_missing_fields_are_rejected(): void
    {
        $this->postJson('/login', ['email' => 'unknown@example.com', 'password' => 'incorrect'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', ['email' => 'limited@example.com', 'password' => 'incorrect'])
                ->assertUnprocessable();
        }
        $this->postJson('/login', ['email' => 'limited@example.com', 'password' => 'incorrect'])
            ->assertStatus(429);
        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session_and_regenerates_the_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/user/'.$user->id);
        $this->withSession(['private-value' => 'removed']);
        $sessionId = session()->getId();
        $csrf = session()->token();

        $this->post('/logout')->assertRedirect('/')->assertSessionMissing('private-value');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrf, session()->token());
    }
}
