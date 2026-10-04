<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('homepage.index')
            ->assertSee('Connexion')
            ->assertSee(route('signin'));
    }

    public function test_the_dashboard_is_available_at_its_new_url(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertViewIs('welcome');
    }
}
