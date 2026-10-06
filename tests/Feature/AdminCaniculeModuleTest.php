<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Alerte;
use App\Models\ReleveMeteo;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCaniculeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_canicul_pages_are_restricted_to_administrators(): void
    {
        $this->get('/admin/canicule/carte')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/admin/canicule/carte')->assertForbidden();
    }

    public function test_admin_can_manage_zones_weather_reports_and_alerts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seed(\Database\Seeders\ZonesSeeder::class);
        $zone = Zone::where('nom', 'Tunis')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/canicule/releves', [
                'zone_id' => $zone->id,
                'type' => 'observation',
                'date_mesure' => now()->format('Y-m-d H:i:s'),
                'temperature' => 39.5,
                'humidite' => 28,
                'vitesse_vent' => 14,
                'indice_uv' => 8,
                'source' => 'Saisie manuelle',
            ])->assertRedirect('/admin/canicule/releves');

        $this->post('/admin/canicule/alertes', [
            'zone_id' => $zone->id,
            'titre' => 'Vague de chaleur',
            'description' => 'Restez hydratés.',
            'niveau_gravite' => 'orange',
            'debut_validite' => now()->subHour()->format('Y-m-d H:i:s'),
            'fin_validite' => now()->addDay()->format('Y-m-d H:i:s'),
            'statut' => 'active',
        ])->assertRedirect('/admin/canicule/alertes');

        $this->assertDatabaseHas('releves_meteo', ['zone_id' => $zone->id, 'temperature' => 39.5]);
        $this->assertDatabaseHas('alertes', ['zone_id' => $zone->id, 'titre' => 'Vague de chaleur', 'created_by' => $admin->id]);

        $releve = ReleveMeteo::firstOrFail();
        $this->get('/admin/canicule/releves/'.$releve->id.'/edit')->assertOk()->assertSee('Modifier le relevé');
        $this->put('/admin/canicule/releves/'.$releve->id, [
            'zone_id' => $zone->id,
            'type' => 'observation',
            'date_mesure' => now()->format('Y-m-d H:i:s'),
            'temperature' => 40,
            'humidite' => 25,
            'vitesse_vent' => 12,
            'indice_uv' => 9,
            'source' => 'Saisie corrigée',
        ])->assertRedirect('/admin/canicule/releves');

        $alerte = Alerte::firstOrFail();
        $this->get('/admin/canicule/alertes/'.$alerte->id.'/edit')->assertOk()->assertSee('Modifier l’alerte');
        $this->put('/admin/canicule/alertes/'.$alerte->id, [
            'zone_id' => $zone->id,
            'titre' => 'Alerte chaleur mise à jour',
            'description' => 'Restez hydratés et évitez les sorties.',
            'niveau_gravite' => 'rouge',
            'debut_validite' => now()->subHour()->format('Y-m-d H:i:s'),
            'fin_validite' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'statut' => 'active',
        ])->assertRedirect('/admin/canicule/alertes');

        $this->assertDatabaseHas('releves_meteo', ['id' => $releve->id, 'temperature' => 40, 'source' => 'Saisie corrigée']);
        $this->assertDatabaseHas('alertes', ['id' => $alerte->id, 'titre' => 'Alerte chaleur mise à jour', 'niveau_gravite' => 'rouge']);
        $this->get('/admin/canicule/carte')->assertOk()->assertViewHas('geojson', function (string $geojson): bool {
            $collection = json_decode($geojson, true, flags: JSON_THROW_ON_ERROR);
            $tunis = collect($collection['features'])->firstWhere('properties.nom', 'Tunis');

            return ($tunis['properties']['alerte'] ?? null) === 'Alerte chaleur mise à jour';
        });
    }

    public function test_admin_can_update_a_seeded_zone_without_losing_its_geometry(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->seed(\Database\Seeders\ZonesSeeder::class);
        $zone = Zone::where('nom', 'Tunis')->firstOrFail();
        $geometry = $zone->geometrie_geojson;

            $this->get('/admin/canicule/zones/'.$zone->id.'/edit')
                ->assertOk()
                ->assertDontSee('name="geometrie_geojson"', false)
                ->assertSee('Frontière GeoJSON protégée');

        $this->put('/admin/canicule/zones/'.$zone->id, [
            'code' => 'FAUX',
            'nom' => 'Géométrie modifiée',
            'geometrie_geojson' => '{"type":"Point","coordinates":[0,0]}',
            'active' => 0,
        ])->assertRedirect('/admin/canicule/zones');

        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'nom' => 'Tunis',
            'code' => 'TUNIS',
            'active' => 0,
            'geometrie_geojson' => $geometry,
        ]);
    }
}