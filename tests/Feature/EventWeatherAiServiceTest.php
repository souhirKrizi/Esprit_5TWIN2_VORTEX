<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Services\EventWeatherAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventWeatherAiServiceTest extends TestCase
{
    use RefreshDatabase;

    private const METEO = 'https://api.openweathermap.org/data/2.5/forecast*';

    private const IA = 'https://generativelanguage.googleapis.com/v1beta/models/*';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['services.openweather.key' => 'cle-meteo', 'assistant_ia.key' => 'cle-ia']);
    }

    private function evenement(): Event
    {
        return Event::create([
            'title' => 'Festival',
            'date_event' => now()->addDay()->setTime(12, 0),
            'governorate' => 'Tunis',
            'location' => 'Centre-ville',
            'description' => 'Festival en plein air',
            'type' => 'festival',
            'status' => 'active',
        ]);
    }

    private function prevision(string $date, float $temperature, int $humidite): array
    {
        return [
            'dt_txt' => $date.' 12:00:00',
            'main' => ['temp' => $temperature, 'humidity' => $humidite],
            'wind' => ['speed' => 5.0],
            'weather' => [['main' => 'Clear']],
        ];
    }

    private function resumeParDefaut(float $temperature, int $humidite, string $risque): string
    {
        return sprintf('Le risque pour Festival est %s avec une température prévue de %.1f°C et une humidité de %d%%.', $risque, $temperature, $humidite);
    }

    public function test_sans_cles_le_service_utilise_les_valeurs_par_defaut_sans_appel_externe(): void
    {
        config(['services.openweather.key' => null, 'assistant_ia.key' => null]);

        $resultat = app(EventWeatherAiService::class)->analyze($this->evenement());

        $this->assertSame('fallback', $resultat['weather']['source']);
        $this->assertSame('orange', $resultat['risk_level']);
        $this->assertSame($this->resumeParDefaut(32.0, 55, 'orange'), $resultat['ai_summary']);
        Http::assertNothingSent();
    }

    public function test_si_la_meteo_echoue_le_service_utilise_les_valeurs_par_defaut(): void
    {
        Http::fake([
            self::METEO => Http::response([], 500),
            self::IA => Http::response([], 500),
        ]);

        $resultat = app(EventWeatherAiService::class)->analyze($this->evenement());

        $this->assertSame('fallback', $resultat['weather']['source']);
        $this->assertSame($this->resumeParDefaut(32.0, 55, 'orange'), $resultat['ai_summary']);
    }

    public function test_si_les_api_ne_repondent_pas_le_service_utilise_les_valeurs_par_defaut(): void
    {
        Http::fake([
            self::METEO => Http::failedConnection(),
            self::IA => Http::failedConnection(),
        ]);

        $resultat = app(EventWeatherAiService::class)->analyze($this->evenement());

        $this->assertSame('fallback', $resultat['weather']['source']);
        $this->assertSame($this->resumeParDefaut(32.0, 55, 'orange'), $resultat['ai_summary']);
    }

    public function test_la_prevision_du_jour_de_l_evenement_est_choisie(): void
    {
        Http::fake([
            self::METEO => Http::response(['list' => [
                $this->prevision(now()->format('Y-m-d'), 40.0, 30),
                $this->prevision(now()->addDay()->format('Y-m-d'), 25.0, 40),
            ]]),
            self::IA => Http::response(['candidates' => [['content' => ['parts' => [['text' => '  <b>Risque</b> faible.  ']]]]]]),
        ]);

        $resultat = app(EventWeatherAiService::class)->analyze($this->evenement());

        $this->assertSame(25.0, $resultat['weather']['temperature']);
        $this->assertSame('openweather', $resultat['weather']['source']);
        $this->assertSame('vert', $resultat['risk_level']);
        $this->assertSame('Risque faible.', $resultat['ai_summary']);
        $this->assertCount(2, $resultat['recommendations']);
    }

    public function test_sans_prevision_pour_ce_jour_la_premiere_est_utilisee(): void
    {
        Http::fake([
            self::METEO => Http::response(['list' => [$this->prevision('2000-01-01', 39.0, 20)]]),
            self::IA => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Risque élevé.']]]]]]),
        ]);

        $resultat = app(EventWeatherAiService::class)->analyze($this->evenement());

        $this->assertSame(39.0, $resultat['weather']['temperature']);
        $this->assertSame('rouge', $resultat['risk_level']);
        $this->assertCount(3, $resultat['recommendations']);
    }

    public function test_une_reponse_ia_vide_donne_le_resume_par_defaut(): void
    {
        Http::fake([
            self::METEO => Http::response(['list' => [$this->prevision(now()->addDay()->format('Y-m-d'), 33.0, 40)]]),
            self::IA => Http::response(['candidates' => [['content' => ['parts' => [['text' => '   ']]]]]]),
        ]);

        $resultat = app(EventWeatherAiService::class)->analyze($this->evenement());

        $this->assertSame('orange', $resultat['risk_level']);
        $this->assertSame($this->resumeParDefaut(33.0, 40, 'orange'), $resultat['ai_summary']);
        $this->assertCount(3, $resultat['recommendations']);
    }
}
