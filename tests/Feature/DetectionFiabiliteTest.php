<?php

namespace Tests\Feature;

use App\Exceptions\AnalyseIaIndisponibleException;
use App\Jobs\AnalyserSignalementJob;
use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use App\Services\AnalyseFiabiliteService;
use App\Services\DetectionDoublonService;
use App\Services\ScoreSuspicionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DetectionFiabiliteTest extends TestCase
{
    use RefreshDatabase;

    private const FAKE_KEY = 'TEST_SECRET_API_KEY_987654321';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'assistant_ia.enabled'                 => true,
            'assistant_ia.analyse_enabled'         => true,
            'assistant_ia.driver'                  => 'gemini',
            'assistant_ia.key'                     => self::FAKE_KEY,
            'assistant_ia.model'                   => 'gemini-3.5-flash-lite',
            'assistant_ia.timeout'                 => 5,
            'assistant_ia.doublons_rayon_m'        => 200,
            'assistant_ia.doublons_fenetre_heures' => 48,
        ]);
    }

    private function fakeGeminiFiabilite(int $score, string $verdict, string $raison, int $status = 200): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode(['score' => $score, 'verdict' => $verdict, 'raison' => $raison])],
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

    // =========================================================================
    // COUCHE 1 : DOUBLONS GÉOGRAPHIQUES
    // =========================================================================

    public function test_doublon_service_detecte_un_signalement_a_moins_de_200m(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new DetectionDoublonService();

        // Signalement de référence à Tunis
        $original = Signalement::factory()->create([
            'user_id'           => $user->id,
            'type_signalement'  => Signalement::TYPE_INCIDENT,
            'type_incident'     => 'point_eau_hors_service',
            'latitude'          => 36.8124000,
            'longitude'         => 10.1763000,
            'statut_validation' => Signalement::STATUT_VALIDE,
            'created_at'        => now()->subHour(),
        ]);

        // Point à environ 80m : delta ~0.0006 deg
        $similaires = $service->trouverSimilaires(
            lat: 36.8129000,
            lng: 10.1763000,
            typeSignalement: Signalement::TYPE_INCIDENT,
            typeIncident: 'point_eau_hors_service',
        );

        $this->assertCount(1, $similaires);
        $this->assertEquals($original->id, $similaires->first()->id);
    }

    public function test_doublon_service_ignore_un_signalement_a_plus_de_200m(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new DetectionDoublonService();

        // Signalement de référence
        Signalement::factory()->create([
            'user_id'           => $user->id,
            'type_signalement'  => Signalement::TYPE_INCIDENT,
            'type_incident'     => 'point_eau_hors_service',
            'latitude'          => 36.8124000,
            'longitude'         => 10.1763000,
            'statut_validation' => Signalement::STATUT_VALIDE,
            'created_at'        => now()->subHour(),
        ]);

        // Point à environ 600m : delta ~0.0055 deg
        $similaires = $service->trouverSimilaires(
            lat: 36.8180000,
            lng: 10.1763000,
            typeSignalement: Signalement::TYPE_INCIDENT,
            typeIncident: 'point_eau_hors_service',
        );

        $this->assertCount(0, $similaires);
    }

    public function test_doublon_service_ignore_les_types_ou_incidents_differents(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new DetectionDoublonService();

        // Point d'eau hors service
        Signalement::factory()->create([
            'user_id'           => $user->id,
            'type_signalement'  => Signalement::TYPE_INCIDENT,
            'type_incident'     => 'point_eau_hors_service',
            'latitude'          => 36.8124000,
            'longitude'         => 10.1763000,
            'statut_validation' => Signalement::STATUT_VALIDE,
        ]);

        // Même endroit, mais coupure électricité
        $similaires = $service->trouverSimilaires(
            lat: 36.8124000,
            lng: 10.1763000,
            typeSignalement: Signalement::TYPE_INCIDENT,
            typeIncident: 'coupure_electricite',
        );

        $this->assertCount(0, $similaires);

        // Même endroit, mais type ressource_ok
        $similairesOk = $service->trouverSimilaires(
            lat: 36.8124000,
            lng: 10.1763000,
            typeSignalement: Signalement::TYPE_RESSOURCE_OK,
            typeIncident: null,
        );

        $this->assertCount(0, $similairesOk);
    }

    public function test_doublon_service_ignore_les_anciens_ou_rejetes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new DetectionDoublonService();

        // Signalement de 3 jours (hors fenêtre 48h)
        Signalement::factory()->create([
            'user_id'           => $user->id,
            'type_signalement'  => Signalement::TYPE_INCIDENT,
            'type_incident'     => 'point_eau_hors_service',
            'latitude'          => 36.8124000,
            'longitude'         => 10.1763000,
            'statut_validation' => Signalement::STATUT_VALIDE,
            'created_at'        => now()->subHours(72),
        ]);

        // Signalement rejeté
        Signalement::factory()->create([
            'user_id'           => $user->id,
            'type_signalement'  => Signalement::TYPE_INCIDENT,
            'type_incident'     => 'point_eau_hors_service',
            'latitude'          => 36.8124000,
            'longitude'         => 10.1763000,
            'statut_validation' => Signalement::STATUT_REJETE,
            'created_at'        => now()->subHour(),
        ]);

        $similaires = $service->trouverSimilaires(
            lat: 36.8124000,
            lng: 10.1763000,
            typeSignalement: Signalement::TYPE_INCIDENT,
            typeIncident: 'point_eau_hors_service',
        );

        $this->assertCount(0, $similaires);
    }

    // =========================================================================
    // COUCHE 2 : RÈGLES ANTI-SPAM
    // =========================================================================

    public function test_score_suspicion_applique_les_regles_de_base(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new ScoreSuspicionService();

        // 1. URL (+30)
        $sUrl = Signalement::factory()->make([
            'user_id'     => $user->id,
            'commentaire' => 'Visitez https://mon-site-spam.com pour voir les détails !',
        ]);
        $resUrl = $service->calculer($sUrl);
        $this->assertGreaterThanOrEqual(30, $resUrl['score']);
        $this->assertContains('URL, e-mail ou numéro de téléphone dans la description', $resUrl['motifs']);

        // 2. Numéro de téléphone (+30)
        $sTel = Signalement::factory()->make([
            'user_id'     => $user->id,
            'commentaire' => 'Appelez le 98123456 pour réparer rapidement la pompe.',
        ]);
        $resTel = $service->calculer($sTel);
        $this->assertGreaterThanOrEqual(30, $resTel['score']);

        // 3. Répétition de caractères (+25)
        $sRep = Signalement::factory()->make([
            'user_id'     => $user->id,
            'commentaire' => 'Panneeeeeee totale de robinet.',
        ]);
        $resRep = $service->calculer($sRep);
        $this->assertGreaterThanOrEqual(25, $resRep['score']);
        $this->assertContains('Texte répétitif ou ratio de mots uniques insuffisant', $resRep['motifs']);

        // 4. Majuscules intégrales (+15)
        $sMaj = Signalement::factory()->make([
            'user_id'     => $user->id,
            'commentaire' => 'IL N Y A PLUS D EAU DU TOUT ICI !',
        ]);
        $resMaj = $service->calculer($sMaj);
        $this->assertGreaterThanOrEqual(15, $resMaj['score']);
        $this->assertContains('Description entièrement en majuscules ou sans lettre', $resMaj['motifs']);
    }

    public function test_score_suspicion_detecte_le_copier_coller(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new ScoreSuspicionService();

        // Ancien signalement
        Signalement::factory()->create([
            'user_id'     => $user->id,
            'commentaire' => 'Le robinet du parc est complètement cassé et fuit partout.',
        ]);

        // Nouveau signalement quasi-identique du même utilisateur
        $nouveau = Signalement::factory()->make([
            'user_id'     => $user->id,
            'commentaire' => 'Le robinet du parc est complètement cassé et fuit partout !',
        ]);

        $res = $service->calculer($nouveau);
        $this->assertGreaterThanOrEqual(30, $res['score']);
        $this->assertContains('Description identique ou quasi identique à un signalement précédent', $res['motifs']);
    }

    public function test_score_suspicion_detecte_la_rafale(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new ScoreSuspicionService();

        // 6 signalements dans la dernière heure
        Signalement::factory()->count(6)->create([
            'user_id'    => $user->id,
            'created_at' => now()->subMinutes(15),
        ]);

        $nouveau = Signalement::factory()->make(['user_id' => $user->id]);
        $res = $service->calculer($nouveau);

        $this->assertGreaterThanOrEqual(25, $res['score']);
        $this->assertContains('Plus de 5 signalements envoyés dans la dernière heure', $res['motifs']);
    }

    public function test_score_suspicion_detecte_une_ressource_trop_eloignee(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new ScoreSuspicionService();

        $ressource = RessourceUtile::factory()->create([
            'latitude'  => 36.8124000,
            'longitude' => 10.1763000,
        ]);

        // Signalement positionné à 5 km de la ressource
        $signalement = Signalement::factory()->make([
            'user_id'            => $user->id,
            'ressource_utile_id' => $ressource->id,
            'latitude'           => 36.8500000,
            'longitude'          => 10.1763000,
            'commentaire'        => 'Panne constatée sur le point d\'eau.',
        ]);
        $signalement->setRelation('ressourceUtile', $ressource);

        $res = $service->calculer($signalement);
        $this->assertGreaterThanOrEqual(20, $res['score']);
        $this->assertContains('Position à plus de 1 km de la ressource sélectionnée', $res['motifs']);
    }

    public function test_score_suspicion_detecte_l_historique_de_rejets(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $service = new ScoreSuspicionService();

        // 4 signalements dont 3 rejetés (75% de rejets)
        Signalement::factory()->count(3)->create([
            'user_id'           => $user->id,
            'statut_validation' => Signalement::STATUT_REJETE,
        ]);
        Signalement::factory()->create([
            'user_id'           => $user->id,
            'statut_validation' => Signalement::STATUT_VALIDE,
        ]);

        $nouveau = Signalement::factory()->make(['user_id' => $user->id, 'commentaire' => 'Description normale']);
        $res = $service->calculer($nouveau);

        $this->assertGreaterThanOrEqual(20, $res['score']);
        $this->assertContains('Historique : plus de 50 % des signalements précédents rejetés', $res['motifs']);
    }

    // =========================================================================
    // COUCHE 3 : ANALYSE IA ET JOB ASYNCHRONE
    // =========================================================================

    public function test_analyse_ia_service_parse_le_json_et_valide_la_reponse(): void
    {
        $this->fakeGeminiFiabilite(85, 'spam', 'Publicité déguisée');

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create([
            'user_id'          => $user->id,
            'type_signalement' => Signalement::TYPE_INCIDENT,
            'type_incident'    => 'autre',
            'commentaire'      => 'Achetez nos ventilateurs au meilleur prix',
        ]);

        $service = new AnalyseFiabiliteService();
        $resultat = $service->analyser($signalement);

        $this->assertEquals(85, $resultat['score']);
        $this->assertEquals('spam', $resultat['verdict']);
        $this->assertEquals('Publicité déguisée', $resultat['raison']);
    }

    public function test_analyse_ia_leve_une_exception_si_reponse_invalide(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => 'ceci n est pas du json']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create(['user_id' => $user->id]);

        $service = new AnalyseFiabiliteService();

        $this->expectException(AnalyseIaIndisponibleException::class);
        $service->analyser($signalement);
    }

    public function test_analyser_signalement_job_combine_regles_et_ia(): void
    {
        // IA renvoie score 80 / spam
        $this->fakeGeminiFiabilite(80, 'spam', 'Spam publicitaire');

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create([
            'user_id'            => $user->id,
            'ressource_utile_id' => null,
            'type_signalement'   => Signalement::TYPE_INCIDENT,
            'type_incident'      => 'autre',
            'commentaire'        => 'Visitez http://spam.tn pour des réductions exceptionnelles',
            'latitude'           => 36.8124000,
            'longitude'          => 10.1763000,
        ]);

        $job = new AnalyserSignalementJob($signalement->id);
        $job->handle(
            app(DetectionDoublonService::class),
            app(ScoreSuspicionService::class),
            app(AnalyseFiabiliteService::class),
        );

        $signalement->refresh();

        // Règles = URL (30). IA = 80.
        // Score final = round(0.4 * 30 + 0.6 * 80) = 12 + 48 = 60 (eleve)
        $this->assertEquals(60, $signalement->score_suspicion);
        $this->assertEquals(Signalement::NIVEAU_ELEVE, $signalement->niveau_suspicion);
        $this->assertEquals(Signalement::ANALYSE_ANALYSE, $signalement->analyse_ia_statut);
        $this->assertNotNull($signalement->analyse_at);
        $this->assertContains('URL, e-mail ou numéro de téléphone dans la description', $signalement->motifs_suspicion);
        $this->assertContains('IA : Spam publicitaire', $signalement->motifs_suspicion);
    }

    public function test_analyser_signalement_job_resiste_si_ia_indisponible(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([], 503),
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $signalement = Signalement::factory()->create([
            'user_id'            => $user->id,
            'ressource_utile_id' => null,
            'type_signalement'   => Signalement::TYPE_INCIDENT,
            'type_incident'      => 'autre',
            'commentaire'        => 'Visitez http://spam.tn pour des réductions',
            'latitude'           => 36.8124000,
            'longitude'          => 10.1763000,
        ]);

        $job = new AnalyserSignalementJob($signalement->id);
        $job->handle(
            app(DetectionDoublonService::class),
            app(ScoreSuspicionService::class),
            app(AnalyseFiabiliteService::class),
        );

        $signalement->refresh();

        // Score basé sur les règles seules (30)
        $this->assertEquals(30, $signalement->score_suspicion);
        $this->assertEquals(Signalement::NIVEAU_MOYEN, $signalement->niveau_suspicion);
        $this->assertEquals(Signalement::ANALYSE_INDISPONIBLE, $signalement->analyse_ia_statut);
        $this->assertContains('URL, e-mail ou numéro de téléphone dans la description', $signalement->motifs_suspicion);
    }

    // =========================================================================
    // CONTRÔLEUR VERIFICATION-DOUBLON
    // =========================================================================

    public function test_verifier_doublon_refuse_les_invites_et_les_non_citoyens(): void
    {
        // Non authentifié
        $this->postJson(route('user.signalements.verifier-doublon'), [
            'latitude'         => 36.8124,
            'longitude'        => 10.1763,
            'type_signalement' => 'incident',
        ])->assertUnauthorized();

        // Administrateur (role != 'user')
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->postJson(route('user.signalements.verifier-doublon'), [
                'latitude'         => 36.8124,
                'longitude'        => 10.1763,
                'type_signalement' => 'incident',
            ])->assertForbidden();
    }

    public function test_verifier_doublon_renvoie_le_nombre_et_le_message_pour_un_citoyen(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Signalement existant à 36.8124, 10.1763
        Signalement::factory()->create([
            'user_id'           => $user->id,
            'type_signalement'  => Signalement::TYPE_INCIDENT,
            'type_incident'     => 'point_eau_hors_service',
            'latitude'          => 36.8124000,
            'longitude'         => 10.1763000,
            'statut_validation' => Signalement::STATUT_VALIDE,
            'created_at'        => now()->subHour(),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('user.signalements.verifier-doublon'), [
                'latitude'         => 36.8128000,
                'longitude'        => 10.1763000,
                'type_signalement' => 'incident',
                'type_incident'    => 'point_eau_hors_service',
            ]);

        $response->assertOk()
            ->assertJson([
                'similaires' => 1,
            ]);

        $this->assertStringContainsString('incident similaire', $response->json('message'));
        // Vérification de sécurité : aucune info personnelle renvoyée
        $this->assertArrayNotHasKey('user_id', $response->json());
        $this->assertArrayNotHasKey('nom', $response->json());
        $this->assertArrayNotHasKey('email', $response->json());
    }

    public function test_verifier_doublon_valide_les_coordonnees_en_tunisie(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Latitude hors Tunisie (e.g. Paris 48.85)
        $this->actingAs($user)
            ->postJson(route('user.signalements.verifier-doublon'), [
                'latitude'         => 48.8566,
                'longitude'        => 2.3522,
                'type_signalement' => 'incident',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    // =========================================================================
    // STORE RESILIENCE
    // =========================================================================

    public function test_store_fonctionne_meme_si_l_api_ia_est_en_erreur(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('user.signalements.store'), [
            'type_signalement' => 'incident',
            'type_incident'    => 'autre',
            'latitude'         => 36.8124000,
            'longitude'        => 10.1763000,
            'commentaire'      => 'Ceci est un incident valide pour tester la resilience.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('signalements', [
            'user_id'     => $user->id,
            'commentaire' => 'Ceci est un incident valide pour tester la resilience.',
        ]);
    }
}
