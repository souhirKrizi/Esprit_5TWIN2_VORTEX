<?php

namespace Tests\Feature;

use App\Jobs\AnalyserImagesSignalementJob;
use App\Models\PieceJointe;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyseImageTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_KEY = 'TEST_SECRET_API_KEY_VISION_12345';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'assistant_ia.enabled'        => true,
            'assistant_ia.vision_enabled' => true,
            'assistant_ia.driver'         => 'gemini',
            'assistant_ia.key'            => self::FAKE_KEY,
            'assistant_ia.vision_model'   => 'gemini-3.5-flash-lite',
            'assistant_ia.vision_timeout' => 5,
        ]);
    }

    private function fakeGeminiVision(array $payload, int $status = 200): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($payload)],
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

    private function creerImageValide(string $nom = 'image.png'): UploadedFile
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $bytes[18] = "\x01";
        $bytes[19] = "\x00";
        $bytes[22] = "\x01";
        $bytes[23] = "\x00";

        return UploadedFile::fake()->createWithContent($nom, $bytes);
    }

    public function test_succes_remplit_les_colonnes_ia_image_et_ne_change_pas_statut_validation(): void
    {
        Storage::fake('public');

        $this->fakeGeminiVision([
            'pertinente' => true,
            'categorie'  => 'point_eau',
            'confiance'  => 92,
            'resume'     => 'Fontaine publique visible et fonctionnelle.',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create([
            'user_id'           => $user->id,
            'statut_validation' => Signalement::STATUT_EN_ATTENTE,
        ]);

        $filePath = 'signalements/test-fontaine.jpg';
        Storage::disk('public')->put($filePath, 'fake-jpeg-content');

        PieceJointe::factory()->create([
            'signalement_id' => $signalement->id,
            'chemin_fichier' => $filePath,
            'type_mime'      => 'image/jpeg',
            'taille'         => 1024,
        ]);

        (new AnalyserImagesSignalementJob($signalement->id))->handle(app(\App\Services\AnalyseImageService::class));

        $signalement->refresh();

        $this->assertEquals(Signalement::IA_IMAGE_ANALYSE, $signalement->ia_image_statut);
        $this->assertTrue($signalement->ia_image_pertinente);
        $this->assertEquals(92, $signalement->ia_image_confiance);
        $this->assertEquals('point_eau', $signalement->ia_image_categorie);
        $this->assertEquals('Fontaine publique visible et fonctionnelle.', $signalement->ia_image_resume);
        $this->assertNotNull($signalement->ia_image_analyse_le);

        // L'IA ne doit JAMAIS changer le statut_validation
        $this->assertEquals(Signalement::STATUT_EN_ATTENTE, $signalement->statut_validation);

        // Vérification de l'accesseur ia_image_badge
        $badge = $signalement->ia_image_badge;
        $this->assertNotNull($badge);
        $this->assertEquals('Image cohérente', $badge['label']);
        $this->assertEquals('vert', $badge['color']);
    }

    public function test_json_invalide_ou_confiance_hors_bornes_donne_statut_indisponible(): void
    {
        Storage::fake('public');

        // Confiance hors bornes (> 100)
        $this->fakeGeminiVision([
            'pertinente' => true,
            'categorie'  => 'point_eau',
            'confiance'  => 140,
            'resume'     => 'Invalide',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);

        $filePath = 'signalements/test-hors-bornes.jpg';
        Storage::disk('public')->put($filePath, 'fake-image-bytes');

        PieceJointe::factory()->create([
            'signalement_id' => $signalement->id,
            'chemin_fichier' => $filePath,
            'type_mime'      => 'image/jpeg',
            'taille'         => 500,
        ]);

        (new AnalyserImagesSignalementJob($signalement->id))->handle(app(\App\Services\AnalyseImageService::class));

        $signalement->refresh();

        $this->assertEquals(Signalement::IA_IMAGE_INDISPONIBLE, $signalement->ia_image_statut);
        $this->assertNotNull($signalement->ia_image_analyse_le);
        $this->assertDatabaseHas('signalements', ['id' => $signalement->id]);
    }

    public function test_api_en_erreur_ou_timeout_donne_statut_indisponible_sans_bloquer_le_citoyen(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([], 503),
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);

        $filePath = 'signalements/test-panne.jpg';
        Storage::disk('public')->put($filePath, 'fake-bytes');

        PieceJointe::factory()->create([
            'signalement_id' => $signalement->id,
            'chemin_fichier' => $filePath,
            'type_mime'      => 'image/jpeg',
            'taille'         => 800,
        ]);

        (new AnalyserImagesSignalementJob($signalement->id))->handle(app(\App\Services\AnalyseImageService::class));

        $signalement->refresh();

        $this->assertEquals(Signalement::IA_IMAGE_INDISPONIBLE, $signalement->ia_image_statut);
    }

    public function test_signalement_sans_image_ne_fait_aucun_appel_http(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create([
            'user_id' => $user->id,
        ]);

        (new AnalyserImagesSignalementJob($signalement->id))->handle(app(\App\Services\AnalyseImageService::class));

        Http::assertNothingSent();
    }

    public function test_fonctionnalite_desactivee_ou_cle_absente_ne_fait_aucun_appel_http(): void
    {
        Storage::fake('public');

        config(['assistant_ia.vision_enabled' => false]);

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);

        $filePath = 'signalements/test-disabled.jpg';
        Storage::disk('public')->put($filePath, 'fake-bytes');

        PieceJointe::factory()->create([
            'signalement_id' => $signalement->id,
            'chemin_fichier' => $filePath,
            'type_mime'      => 'image/jpeg',
        ]);

        (new AnalyserImagesSignalementJob($signalement->id))->handle(app(\App\Services\AnalyseImageService::class));

        Http::assertNothingSent();

        $signalement->refresh();
        $this->assertEquals(Signalement::IA_IMAGE_INDISPONIBLE, $signalement->ia_image_statut);
    }

    public function test_route_de_relance_reservee_au_proprietaire_et_avec_throttle(): void
    {
        $proprietaire = User::factory()->create(['role' => 'user']);
        $autreUser = User::factory()->create(['role' => 'user']);

        $signalement = Signalement::factory()->create([
            'user_id'         => $proprietaire->id,
            'ia_image_statut' => Signalement::IA_IMAGE_INDISPONIBLE,
        ]);

        PieceJointe::factory()->create([
            'signalement_id' => $signalement->id,
            'chemin_fichier' => 'signalements/test.jpg',
            'type_mime'      => 'image/jpeg',
        ]);

        // Invité -> redirection vers login
        $this->post(route('user.signalements.analyse-image', $signalement))
            ->assertRedirect();

        // Autre citoyen -> 403 Forbidden
        $this->actingAs($autreUser)
            ->post(route('user.signalements.analyse-image', $signalement))
            ->assertForbidden();

        // Propriétaire -> 302 Redirection avec succès
        $this->actingAs($proprietaire)
            ->post(route('user.signalements.analyse-image', $signalement))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Test throttle : 5 requêtes par minute autorisées, la 6ème doit renvoyer 429
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($proprietaire)
                ->post(route('user.signalements.analyse-image', $signalement));
        }

        $this->actingAs($proprietaire)
            ->post(route('user.signalements.analyse-image', $signalement))
            ->assertStatus(429);
    }

    public function test_la_cle_api_n_apparait_dans_aucune_reponse(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $signalement = Signalement::factory()->create([
            'user_id'             => $user->id,
            'ia_image_statut'     => Signalement::IA_IMAGE_ANALYSE,
            'ia_image_pertinente' => true,
            'ia_image_confiance'  => 85,
            'ia_image_resume'     => 'Photo de fontaine municipale.',
        ]);

        // Show
        $resShow = $this->actingAs($user)->get(route('user.signalements.show', $signalement));
        $resShow->assertOk();
        $this->assertStringNotContainsString(self::FAKE_KEY, $resShow->getContent());

        // Index
        $resIndex = $this->actingAs($user)->get(route('user.signalements.index'));
        $resIndex->assertOk();
        $this->assertStringNotContainsString(self::FAKE_KEY, $resIndex->getContent());
    }

    public function test_formulaire_refuse_une_image_detectee_comme_non_pertinente(): void
    {
        Storage::fake('public');

        $this->fakeGeminiVision([
            'pertinente' => false,
            'categorie'  => 'non_pertinent',
            'confiance'  => 100,
            'resume'     => 'Schéma d\'architecture logicielle hors-sujet.',
        ]);

        $user = User::factory()->create(['role' => 'user']);

        $image = $this->creerImageValide('schema.png');

        $response = $this->actingAs($user)
            ->from(route('user.signalements.create'))
            ->post(route('user.signalements.store'), [
                'type_signalement' => 'incident',
                'type_incident'    => 'point_eau_hors_service',
                'latitude'         => 36.8124,
                'longitude'        => 10.1763,
                'commentaire'      => 'Point d\'eau signalé en panne.',
                'images'           => [$image],
            ]);

        $response->assertRedirect(route('user.signalements.create'));
        $response->assertSessionHasErrors(['images']);

        // Le signalement ne doit PAS être créé
        $this->assertDatabaseCount('signalements', 0);
        $this->assertDatabaseCount('pieces_jointes', 0);
    }

    public function test_formulaire_accepte_une_image_pertinente(): void
    {
        Storage::fake('public');

        $this->fakeGeminiVision([
            'pertinente' => true,
            'categorie'  => 'point_eau',
            'confiance'  => 95,
            'resume'     => 'Photo montrant un robinet public cassé.',
        ]);

        $user = User::factory()->create(['role' => 'user']);

        $image = $this->creerImageValide('fontaine.png');

        $response = $this->actingAs($user)
            ->post(route('user.signalements.store'), [
                'type_signalement' => 'incident',
                'type_incident'    => 'point_eau_hors_service',
                'latitude'         => 36.8124,
                'longitude'        => 10.1763,
                'commentaire'      => 'Le robinet fuit depuis ce matin.',
                'images'           => [$image],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('signalements', 1);

        $signalement = Signalement::first();
        $this->assertEquals(Signalement::IA_IMAGE_ANALYSE, $signalement->ia_image_statut);
        $this->assertTrue($signalement->ia_image_pertinente);
        $this->assertEquals('point_eau', $signalement->ia_image_categorie);
    }
}

