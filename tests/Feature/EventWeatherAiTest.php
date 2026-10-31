<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventWeatherAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_weather_analysis_returns_risk_and_ai_recommendations(): void
    {
        config(['assistant_ia.enabled' => true, 'assistant_ia.key' => 'test-key']);
        config(['services.openweather.key' => 'weather-key']);

        Http::fake([
            'https://api.openweathermap.org/data/2.5/forecast*' => Http::response([
                'list' => [
                    [
                        'dt_txt' => now()->addDay()->format('Y-m-d 12:00:00'),
                        'main' => ['temp' => 38.5, 'humidity' => 68],
                        'wind' => ['speed' => 18.2],
                        'weather' => [['main' => 'Clear']],
                    ],
                ],
            ]),
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => 'Risque élevé. Évitez les activités en plein soleil entre 12h et 16h. Hydratez-vous régulièrement.',
                        ]],
                    ],
                ]],
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $event = Event::create([
            'title' => 'Festival de la canicule',
            'date_event' => now()->addDay()->setTime(12, 0),
            'governorate' => 'Tunis',
            'location' => 'Place du 7 novembre',
            'description' => 'Festival en plein air',
            'type' => 'festival',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/user/evenements/'.$event->id.'/analyser');

        $response->assertOk()
            ->assertJsonPath('event.id', $event->id)
            ->assertJsonPath('risk_level', 'rouge')
            ->assertJsonPath('weather.temperature', 38.5)
            ->assertJsonPath('ai_summary', 'Risque élevé. Évitez les activités en plein soleil entre 12h et 16h. Hydratez-vous régulièrement.');
    }
}
