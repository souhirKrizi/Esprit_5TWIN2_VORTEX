<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class ZonesSeeder extends Seeder
{
    public function run(): void
    {
        $path = public_path('assets/heatalert/tunisia-governorates.geojson');
        if (! is_file($path)) {
            throw new RuntimeException('Le fichier GeoJSON des gouvernorats est introuvable.');
        }

        $collection = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        foreach ($collection['features'] as $feature) {
            $name = $feature['properties']['shapeName'];
            Zone::firstOrCreate(
                ['nom' => $name],
                [
                    'code' => Str::upper(Str::slug($name, '_')),
                    'geometrie_geojson' => json_encode($feature['geometry'], JSON_THROW_ON_ERROR),
                    'active' => true,
                ],
            );
        }
    }
}