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

    #[DataProvider('roles')]
    public function test_login_redirects_to_the_stored_role(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->withSession(['url.intended' => '/admin'])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'admin',
        ])->assertRedirect('/'.$role);

        $this->assertAuthenticatedAs($user);
        $this->get('/'.$role)->assertOk()->assertSee('Déconnexion')->assertSee($user->name);
    }

    #[DataProvider('roles')]
    public function test_json_login_returns_the_role_destination(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('redirect', route('spaces.'.$role));
        $this->assertAuthenticatedAs($user);
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
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/user');
        $this->withSession(['private-value' => 'removed']);
        $sessionId = session()->getId();
        $csrf = session()->token();

        $this->post('/logout')->assertRedirect('/')->assertSessionMissing('private-value');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrf, session()->token());
    }
}
