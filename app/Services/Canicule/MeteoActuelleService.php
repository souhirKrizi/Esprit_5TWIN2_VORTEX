<?php

namespace App\Services\Canicule;

use App\Models\Zone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Météo actuelle d'une zone via OpenWeather, partagée par les pages citoyen et administration.
 */
class MeteoActuelleService
{
    public function pourZone(Zone $zone): ?array
    {
        $apiKey = config('services.openweather.key');

        if (blank($apiKey)) {
            return null;
        }

        try {
            $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $zone->nom.',TN',
                'units' => 'metric',
                'lang' => 'fr',
                'appid' => $apiKey,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $main = $data['main'] ?? [];
        $wind = $data['wind'] ?? [];
        $weather = $data['weather'][0] ?? [];

        return [
            'temperature' => (float) ($main['temp'] ?? 0),
            'humidity' => (int) ($main['humidity'] ?? 0),
            'wind_speed' => (float) ($wind['speed'] ?? 0),
            'condition' => (string) ($weather['description'] ?? 'Données indisponibles'),
            'sunrise' => data_get($data, 'sys.sunrise'),
            'sunset' => data_get($data, 'sys.sunset'),
            'updated_at' => data_get($data, 'dt'),
        ];
    }
}
