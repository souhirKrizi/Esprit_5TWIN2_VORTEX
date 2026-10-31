<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantRedactionTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_KEY = 'TEST_SECRET_API_KEY_123456789';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'assistant_ia.enabled' => true,
            'assistant_ia.driver' => 'gemini',
            'assistant_ia.key' => self::FAKE_KEY,
            'assistant_ia.model' => 'gemini-1.5-flash',
            'assistant_ia.timeout' => 5,
        ]);
    }

    private function fakeGemini(string $texte, int $status = 200): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $texte],
                            ],
                            'role' => 'model',
                        ],
                        'finishReason' => 'STOP',
                        'index' => 0,
                    ],
                ],
            ], $status),
        ]);
    }

    public function test_invite_redirige_vers_la_connexion(): void
    {
        $response = $this->post(route('user.signalements.assistant-redaction'), [
            'texte' => 'Point d’eau fermé depuis plusieurs heures.',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_seuls_les_citoyens_peuvent_utiliser_assistant(): void
    {
        $this->fakeGemini('Texte reformulé');

        foreach (['admin', 'agent-campagne'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->postJson(route('user.signalements.assistant-redaction'), [
                    'texte' => 'Coupure d’électricité générale constatée.',
                ])
                ->assertForbidden();
        }
    }

    public function test_texte_trop_court_donne_422_avec_message_francais(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($citoyen)->postJson(route('user.signalements.assistant-redaction'), [
            'texte' => 'eau',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['texte']);

        $message = $response->json('message') ?? $response->json('errors.texte.0');
        $this->assertStringContainsString('au moins 5 caractères', (string) $message);
    }

    public function test_succes_renvoie_200_avec_suggestion_nettoyee(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        $this->fakeGemini("   <b>Le point d'eau du parc est hors service.</b>   \n");

        $response = $this->actingAs($citoyen)->postJson(route('user.signalements.assistant-redaction'), [
            'texte' => 'robinet casse au parc pas deau',
            'type_signalement' => 'incident',
            'type_incident' => 'point_eau_hors_service',
        ]);

        $response->assertOk()
            ->assertJson([
                'suggestion' => "Le point d'eau du parc est hors service.",
            ]);

        $this->assertStringNotContainsString('<b>', $response->json('suggestion'));
    }

    public function test_erreur_api_ou_timeout_renvoie_503_avec_message_francais(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        // Cas 1 : Erreur HTTP de l'API (ex: 500)
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $responseErreur = $this->actingAs($citoyen)->postJson(route('user.signalements.assistant-redaction'), [
            'texte' => 'Coupure totale de courant dans le quartier.',
        ]);

        $responseErreur->assertStatus(503)
            ->assertJson([
                'message' => "L'assistant est indisponible, vous pouvez continuer à écrire votre description.",
            ]);

        // Cas 2 : Timeout ou échec de connexion réseau
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $responseTimeout = $this->actingAs($citoyen)->postJson(route('user.signalements.assistant-redaction'), [
            'texte' => 'Coupure totale de courant dans le quartier.',
        ]);

        $responseTimeout->assertStatus(503)
            ->assertJson([
                'message' => "L'assistant est indisponible, vous pouvez continuer à écrire votre description.",
            ]);
    }

    public function test_onzieme_requete_dans_la_minute_renvoie_429(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $this->fakeGemini('Description reformulée avec succès.');

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($citoyen)
                ->postJson(route('user.signalements.assistant-redaction'), [
                    'texte' => "Test signalement numéro {$i} valide",
                ])
                ->assertOk();
        }

        $response11 = $this->actingAs($citoyen)
            ->postJson(route('user.signalements.assistant-redaction'), [
                'texte' => 'La onzième requête doit être bloquée par le throttle.',
            ]);

        $response11->assertStatus(429);
    }

    public function test_la_cle_api_n_apparait_jamais_dans_la_reponse(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);

        // Test lors d'une réussite
        $this->fakeGemini('Description reformulée');
        $reussite = $this->actingAs($citoyen)->postJson(route('user.signalements.assistant-redaction'), [
            'texte' => 'Description valide sans fuite de clé.',
        ]);
        $this->assertStringNotContainsString(self::FAKE_KEY, (string) $reussite->getContent());

        // Test lors d'une erreur 500
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);
        $echec = $this->actingAs($citoyen)->postJson(route('user.signalements.assistant-redaction'), [
            'texte' => 'Description valide sans fuite lors d\'erreur.',
        ]);
        $this->assertStringNotContainsString(self::FAKE_KEY, (string) $echec->getContent());
    }
}

