<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EventWeatherAiService
{
    public function analyze(Event $event): array
    {
        $weather = $this->fetchWeather($event);
        $risk = $this->buildRiskLevel($weather['temperature'], $weather['humidity']);

        $summary = $this->generateAiSummary($event, $weather, $risk);

        return [
            'weather' => $weather,
            'risk_level' => $risk,
            'ai_summary' => $summary,
            'recommendations' => $this->recommendationsFor($risk),
        ];
    }

    protected function fetchWeather(Event $event): array
    {
        $apiKey = config('services.openweather.key');

        if (blank($apiKey)) {
            return [
                'temperature' => 32.0,
                'humidity' => 55,
                'wind_speed' => 10.0,
                'condition' => 'inconnu',
                'source' => 'fallback',
            ];
        }

        $response = Http::get('https://api.openweathermap.org/data/2.5/forecast', [
            'q' => $event->governorate.',TN',
            'units' => 'metric',
            'appid' => $apiKey,
            'cnt' => 40,
        ]);

        if ($response->failed()) {
            return [
                'temperature' => 32.0,
                'humidity' => 55,
                'wind_speed' => 10.0,
                'condition' => 'inconnu',
                'source' => 'fallback',
            ];
        }

        $forecast = $response->json('list', []);
        $targetDate = $event->date_event?->format('Y-m-d');

        $selection = collect($forecast)->first(function (array $entry) use ($targetDate): bool {
            return isset($entry['dt_txt']) && str_starts_with($entry['dt_txt'], (string) $targetDate);
        });

        if (! is_array($selection)) {
            $selection = collect($forecast)->first() ?? [];
        }

        $main = $selection['main'] ?? [];
        $wind = $selection['wind'] ?? [];
        $weather = $selection['weather'][0] ?? [];

        return [
            'temperature' => (float) ($main['temp'] ?? 32.0),
            'humidity' => (int) ($main['humidity'] ?? 55),
            'wind_speed' => (float) ($wind['speed'] ?? 10.0),
            'condition' => $weather['main'] ?? 'inconnu',
            'source' => 'openweather',
        ];
    }

    protected function buildRiskLevel(float $temperature, int $humidity): string
    {
        if ($temperature >= 38 || $humidity >= 70) {
            return 'rouge';
        }

        if ($temperature >= 32 || $humidity >= 60) {
            return 'orange';
        }

        return 'vert';
    }

    protected function generateAiSummary(Event $event, array $weather, string $risk): string
    {
        $apiKey = config('assistant_ia.key');

        if (blank($apiKey)) {
            return sprintf(
                'Le risque pour %s est %s avec une température prévue de %.1f°C et une humidité de %d%%.',
                $event->title,
                $risk,
                $weather['temperature'],
                $weather['humidity'],
            );
        }

        $prompt = sprintf(
            "Tu es un assistant de prévention pour un événement extérieur. Analyse ce contexte et donne 1 à 2 phrases utiles pour la sécurité. "
            . "Événement : %s. Gouvernement : %s. Date : %s. Température : %.1f°C. Humidité : %d%%. Vent : %.1f km/h. Niveau de risque : %s. "
            . "Réponds uniquement avec un texte clair et concis.",
            $event->title,
            $event->governorate,
            $event->date_event?->format('d/m/Y'),
            $weather['temperature'],
            $weather['humidity'],
            $weather['wind_speed'],
            $risk,
        );

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->timeout(15)->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('assistant_ia.model', 'gemini-1.5-flash').':generateContent', [
            'contents' => [[
                'role' => 'user',
                'parts' => [[
                    'text' => $prompt,
                ]],
            ]],
        ]);

        if ($response->failed()) {
            return sprintf(
                'Le risque pour %s est %s avec une température prévue de %.1f°C et une humidité de %d%%.',
                $event->title,
                $risk,
                $weather['temperature'],
                $weather['humidity'],
            );
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            return sprintf(
                'Le risque pour %s est %s avec une température prévue de %.1f°C et une humidité de %d%%.',
                $event->title,
                $risk,
                $weather['temperature'],
                $weather['humidity'],
            );
        }

        return trim(strip_tags($text));
    }

    protected function recommendationsFor(string $risk): array
    {
        return match ($risk) {
            'rouge' => [
                'Hydratez-vous régulièrement et planifiez des pauses à l’ombre.',
                'Évitez les activités intenses au plus fort de la chaleur.',
                'Préparez un plan d’urgence avec eau et matériel de sécurité.',
            ],
            'orange' => [
                'Surveillez la température et les signes de fatigue.',
                'Proposez des zones ombragées et des points de repos.',
                'Restez attentif aux messages de prévention.',
            ],
            default => [
                'Gardez une vigilance normale et suivez l’évolution météo.',
                'Des zones de repos et une hydratation simple restent utiles.',
            ],
        };
    }
}
