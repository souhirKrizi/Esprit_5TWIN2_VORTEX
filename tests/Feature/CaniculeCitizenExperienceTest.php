<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\ReleveMeteo;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaniculeCitizenExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Les appels à l'IA sont simulés : le test ne doit pas dépendre d'une vraie clé dans .env.
        config(['assistant_ia.key' => 'test-key']);
    }

    public function test_les_pages_restent_disponibles_si_les_api_ne_repondent_pas(): void
    {
        config(['services.openweather.key' => 'test-key']);
        Http::fake(fn () => Http::failedConnection());

        $user = User::factory()->create(['role' => 'user', 'governorate' => 'Tunis']);
        $zone = Zone::create(['code' => 'TN-11', 'nom' => 'Tunis', 'geometrie_geojson' => '{}', 'active' => true]);
        Campagne::create([
            'organisateur_id' => $user->id,
            'titre' => 'Campagne sans réseau',
            'description' => 'Événement en plein air',
            'objectif' => 'Sensibilisation',
            'lieu' => 'Tunis',
            'date_debut' => now()->addDay(),
            'date_fin' => now()->addDays(2),
            'statut' => 'publiee',
        ]);

        $this->actingAs($user)->get('/user/canicule/ma-zone?zone_id='.$zone->id)->assertOk();
        $this->actingAs($user)->get('/user/canicule/alertes')->assertOk()
            ->assertSee('Campagne sans réseau')
            ->assertSee('Hydratez-vous régulièrement');
    }

    public function test_user_can_pick_zone_for_weather_and_see_campaign_guidance(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Buvez de l’eau et évitez la chaleur entre 12h et 16h.']]],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['role' => 'user', 'governorate' => 'Tunis']);

        $zone = Zone::create([
            'code' => 'TN-11',
            'nom' => 'Tunis',
            'geometrie_geojson' => '{}',
            'active' => true,
        ]);

        ReleveMeteo::create([
            'zone_id' => $zone->id,
            'type' => 'observation',
            'date_mesure' => now(),
            'temperature' => 36.5,
            'humidite' => 55,
            'vitesse_vent' => 12.5,
            'indice_uv' => 7,
            'source' => 'test',
        ]);

        Campagne::create([
            'organisateur_id' => $user->id,
            'titre' => 'Festival de la canicule',
            'description' => 'Événement en plein air',
            'objectif' => 'Sensibilisation',
            'lieu' => 'Tunis',
            'date_debut' => now()->addDay(),
            'date_fin' => now()->addDays(2),
            'statut' => 'publiee',
        ]);

        Http::fake([
            'https://api.openweathermap.org/data/2.5/weather*' => Http::response([
                'main' => ['temp' => 36.5, 'humidity' => 55],
                'wind' => ['speed' => 12.5],
                'weather' => [['description' => 'chaud']],
                'sys' => ['sunrise' => 1729000000, 'sunset' => 1729040000],
            ], 200),
        ]);
        config(['services.openweather.key' => 'test-key']);

        $this->actingAs($user)
            ->get('/user/canicule/ma-zone?zone_id='.$zone->id)
            ->assertOk()
            ->assertSee('Météo de ma zone')
            ->assertSee('36,5°')
            ->assertSee('Tunis');

        $this->actingAs($user)
            ->get('/user/canicule/alertes')
            ->assertOk()
            ->assertSee('Festival de la canicule')
            ->assertSee('Info :')
            ->assertSee('Actualiser')
            ->assertSee('Buvez de l’eau');
    }

    public function test_user_sees_published_campaigns_when_no_future_campaign_is_available(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Restez hydraté et évitez les heures de forte chaleur.']]],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['role' => 'user', 'governorate' => 'Tunis']);

        Campagne::create([
            'organisateur_id' => $user->id,
            'titre' => 'Campagne déjà planifiée',
            'description' => 'Événement déjà en cours',
            'objectif' => 'Sensibilisation',
            'lieu' => 'Tunis',
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addDay(),
            'statut' => 'publiee',
        ]);

        $this->actingAs($user)
            ->get('/user/canicule/alertes')
            ->assertOk()
            ->assertSee('Campagne déjà planifiée')
            ->assertSee('Restez hydraté');
    }
}
