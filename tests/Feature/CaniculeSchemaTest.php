<?php

namespace Tests\Feature;

use App\Models\Alerte;
use App\Models\ReleveMeteo;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaniculeSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_seed_loads_all_governorate_geometries(): void
    {
        $this->seed(\Database\Seeders\ZonesSeeder::class);

        $this->assertSame(24, Zone::count());
        $this->assertNotEmpty(Zone::where('nom', 'Tunis')->value('geometrie_geojson'));
        $geojson = json_decode(file_get_contents(public_path('assets/heatalert/tunisia-governorates.geojson')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(24, $geojson['features']);
    }

    public function test_weather_reports_and_alerts_belong_to_a_zone(): void
    {
        $this->seed(\Database\Seeders\ZonesSeeder::class);
        $zone = Zone::where('nom', 'Tunis')->firstOrFail();

        $releve = $zone->relevesMeteo()->create([
            'type' => 'observation',
            'date_mesure' => now(),
            'temperature' => 39,
            'humidite' => 30,
            'source' => 'Saisie manuelle',
        ]);
        $alerte = $zone->alertes()->create([
            'titre' => 'Vague de chaleur',
            'description' => 'Forte chaleur prévue.',
            'niveau_gravite' => 'orange',
            'debut_validite' => now(),
            'fin_validite' => now()->addDay(),
            'statut' => 'active',
        ]);

        $this->assertInstanceOf(ReleveMeteo::class, $releve);
        $this->assertInstanceOf(Alerte::class, $alerte);
        $this->assertSame($zone->id, $releve->zone->id);
        $this->assertSame($zone->id, $alerte->zone->id);
    }
}