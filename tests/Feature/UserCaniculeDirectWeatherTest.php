<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserCaniculeDirectWeatherTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_page_displays_live_weather_for_selected_zone(): void
    {
        Http::fake([
            'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
                'main' => ['temp' => 31.2, 'humidity' => 52],
                'wind' => ['speed' => 9.8],
                'weather' => [['description' => 'peu nuageux']],
                'sys' => ['sunrise' => 1729000000, 'sunset' => 1729040000],
            ], 200),
        ]);

        config(['services.openweather.key' => 'user-weather-key']);

        $user = User::factory()->create(['role' => 'user', 'governorate' => 'Tunis']);
        Zone::create([
            'code' => 'TN-11',
            'nom' => 'Tunis',
            'geometrie_geojson' => '{}',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/user/canicule/ma-zone')
            ->assertOk()
            ->assertSee('Météo de ma zone')
            ->assertSee('31,2')
            ->assertSee('52 %')
            ->assertSee('peu nuageux');
    }
}
