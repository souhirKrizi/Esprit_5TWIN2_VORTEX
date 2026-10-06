<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitoyenCaniculePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_sees_weather_and_active_alerts_for_their_governorate(): void
    {
        $this->seed(\Database\Seeders\ZonesSeeder::class);
        $user = User::factory()->create(['role' => 'user', 'governorate' => 'Le Kef']);
        $zone = Zone::where('nom', 'El Kef')->firstOrFail();
        $zone->relevesMeteo()->create([
            'type' => 'observation', 'date_mesure' => now(), 'temperature' => 40.2,
            'humidite' => 22, 'source' => 'Saisie manuelle',
        ]);
        $zone->alertes()->create([
            'titre' => 'Vigilance chaleur', 'description' => 'Hydratez-vous régulièrement.',
            'niveau_gravite' => 'orange', 'debut_validite' => now()->subHour(),
            'fin_validite' => now()->addDay(), 'statut' => 'active',
        ]);
        $zone->alertes()->create([
            'titre' => 'Alerte expirée', 'description' => 'Alerte terminée.',
            'niveau_gravite' => 'rouge', 'debut_validite' => now()->subDays(2),
            'fin_validite' => now()->subDay(), 'statut' => 'active',
        ]);

        $this->actingAs($user)->get('/user/canicule/ma-zone')
            ->assertOk()->assertSee('El Kef')->assertSee('40,2°')->assertSee('Vigilance chaleur');
        $this->get('/user/canicule/alertes')
            ->assertOk()->assertSee('Vigilance chaleur')->assertDontSee('Alerte expirée');
        $this->get('/user/canicule/carte')->assertOk()->assertViewHas('geojson', function (string $geojson): bool {
            $collection = json_decode($geojson, true, flags: JSON_THROW_ON_ERROR);
            $kef = collect($collection['features'])->firstWhere('properties.nom', 'El Kef');

            return ($kef['properties']['niveau'] ?? null) === 'orange';
        });
    }

    public function test_canicul_pages_are_restricted_to_citizen_accounts(): void
    {
        $this->get('/user/canicule/ma-zone')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/user/canicule/alertes')->assertForbidden();
    }
}