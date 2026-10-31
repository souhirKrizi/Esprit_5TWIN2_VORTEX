<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCaniculeWeatherApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_select_zone_and_see_weather_details_from_api(): void
    {
        Http::fake([
            'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
                'main' => ['temp' => 38.5, 'humidity' => 64],
                'wind' => ['speed' => 14.2],
                'weather' => [['description' => 'ensoleillé']],
                'sys' => ['sunrise' => 1729000000, 'sunset' => 1729040000],
            ], 200),
        ]);

        config(['services.openweather.key' => 'test-weather-key']);

        $admin = User::factory()->create(['role' => 'admin']);
        $zone = Zone::create([
            'code' => 'TN-12',
            'nom' => 'Tunis',
            'geometrie_geojson' => '{}',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/canicule/releves?zone_id='.$zone->id)
            ->assertOk()
            ->assertSee('Tunis')
            ->assertSee('38,5')
            ->assertSee('64 %')
            ->assertSee('ensoleillé');
    }
}
