<?php

namespace Tests\Feature;

use App\Models\PieceJointe;
use App\Models\RessourceUtile;
use App\Models\Signalement;
use App\Models\User;
use App\Services\ExportSignalementsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExportSignalementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests(); // Aucune requête API externe
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function createCitoyen(): User
    {
        return User::factory()->create([
            'role' => 'user',
        ]);
    }

    private function createAgentCampagne(): User
    {
        return User::factory()->create([
            'role' => 'agent-campagne',
        ]);
    }

    public function test_invite_est_redirige_vers_connexion(): void
    {
        $response = $this->get('/admin/signalements/export');

        $response->assertRedirect('/login');
    }

    public function test_citoyen_est_refuse_avec_403(): void
    {
        $user = $this->createCitoyen();

        $response = $this->actingAs($user)->get('/admin/signalements/export');

        $response->assertForbidden();
    }

    public function test_agent_campagne_est_refuse_avec_403(): void
    {
        $agent = $this->createAgentCampagne();

        $response = $this->actingAs($agent)->get('/admin/signalements/export');

        $response->assertForbidden();
    }

    public function test_admin_obtient_200_et_content_type_csv(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/signalements/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('signalements_export_', $response->headers->get('Content-Disposition'));
    }

    public function test_fichier_commence_par_bom_utf8(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/signalements/export');

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_fichier_contient_les_deux_parties(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/signalements/export');

        $content = $response->streamedContent();
        $this->assertStringContainsString('RÉSUMÉ PAR GOUVERNORAT', $content);
        $this->assertStringContainsString('DÉTAIL DES SIGNALEMENTS', $content);
    }

    public function test_partie_a_gouvernorat_sans_signalement_non_rattache_et_total(): void
    {
        $admin = $this->createAdmin();

        // 1 ressource sans signalement dans "Sousse"
        RessourceUtile::factory()->create([
            'gouvernorat' => 'Sousse',
            'nom'         => 'Ressource Sousse',
        ]);

        // 1 signalement non rattaché (sans ressource)
        Signalement::factory()->create([
            'user_id'            => $admin->id,
            'ressource_utile_id' => null,
            'statut_validation'  => Signalement::STATUT_EN_ATTENTE,
        ]);

        $response = $this->actingAs($admin)->get('/admin/signalements/export');
        $content = $response->streamedContent();

        // Sousse doit être présent avec nb_ressources = 1 et total_signalements = 0
        $this->assertMatchesRegularExpression('/Sousse;1;0;1;0;0;0;0;0;0;0;0;0;0;/', $content);

        // "Non rattaché" doit être présent avec total_signalements = 1
        $this->assertMatchesRegularExpression('/"?Non rattaché"?;0;0;0;1;1;0;0;0;0;0;0;0;0;/', $content);

        // TOTAL doit additionner les ressources et les signalements (1 ressource, 1 signalement)
        $this->assertMatchesRegularExpression('/TOTAL;1;0;1;1;1;0;0;0;0;0;0;0;0;/', $content);
    }

    public function test_partie_a_decompte_exact_ressources_rouges_et_vertes(): void
    {
        $admin = $this->createAdmin();

        // Ressource 1 à Tunis : aura un incident validé => ROUGE
        $ressourceRouge = RessourceUtile::factory()->create([
            'gouvernorat' => 'Tunis',
            'nom'         => 'Parc du Belvédère',
        ]);
        Signalement::factory()->create([
            'user_id'            => $admin->id,
            'ressource_utile_id' => $ressourceRouge->id,
            'type_signalement'   => Signalement::TYPE_INCIDENT,
            'type_incident'      => 'coupure_electricite',
            'statut_validation'  => Signalement::STATUT_VALIDE,
        ]);

        // Ressource 2 à Tunis : sans incident => VERTE
        RessourceUtile::factory()->create([
            'gouvernorat' => 'Tunis',
            'nom'         => 'Fontaine Habib Bourguiba',
        ]);

        $service = app(ExportSignalementsService::class);
        $resume = $service->resumeParGouvernorat([]);

        $tunis = $resume->get('Tunis');
        $this->assertNotNull($tunis);
        $this->assertEquals(2, $tunis['nb_ressources']);
        $this->assertEquals(1, $tunis['ressources_rouges']);
        $this->assertEquals(1, $tunis['ressources_vertes']);
        $this->assertEquals(1, $tunis['total_signalements']);
        $this->assertEquals(1, $tunis['valides']);
        $this->assertEquals(1, $tunis['incidents_valides']);
        $this->assertEquals(1, $tunis['coupure_electricite']);
    }

    public function test_partie_a_decompte_statuts_exact(): void
    {
        $admin = $this->createAdmin();
        $ressource = RessourceUtile::factory()->create(['gouvernorat' => 'Ariana']);

        // 1 en attente, 2 validés (1 incident, 1 ressource_ok), 1 rejeté
        Signalement::factory()->create([
            'user_id'            => $admin->id,
            'ressource_utile_id' => $ressource->id,
            'type_signalement'   => Signalement::TYPE_INCIDENT,
            'type_incident'      => 'point_eau_hors_service',
            'statut_validation'  => Signalement::STATUT_EN_ATTENTE,
        ]);
        Signalement::factory()->create([
            'user_id'            => $admin->id,
            'ressource_utile_id' => $ressource->id,
            'type_signalement'   => Signalement::TYPE_INCIDENT,
            'type_incident'      => 'point_eau_hors_service',
            'statut_validation'  => Signalement::STATUT_VALIDE,
        ]);
        Signalement::factory()->create([
            'user_id'            => $admin->id,
            'ressource_utile_id' => $ressource->id,
            'type_signalement'   => Signalement::TYPE_RESSOURCE_OK,
            'type_incident'      => null,
            'statut_validation'  => Signalement::STATUT_VALIDE,
        ]);
        Signalement::factory()->create([
            'user_id'            => $admin->id,
            'ressource_utile_id' => $ressource->id,
            'type_signalement'   => Signalement::TYPE_INCIDENT,
            'type_incident'      => 'autre',
            'statut_validation'  => Signalement::STATUT_REJETE,
        ]);

        $service = app(ExportSignalementsService::class);
        $resume = $service->resumeParGouvernorat([]);
        $ariana = $resume->get('Ariana');

        $this->assertEquals(4, $ariana['total_signalements']);
        $this->assertEquals(1, $ariana['en_attente']);
        $this->assertEquals(2, $ariana['valides']);
        $this->assertEquals(1, $ariana['rejetes']);
        $this->assertEquals(1, $ariana['incidents_valides']);
        $this->assertEquals(1, $ariana['point_eau_hors_service']);
        $this->assertEquals(1, $ariana['ressources_ok_valides']);
    }

    public function test_partie_b_filtres_actifs(): void
    {
        $admin = $this->createAdmin();

        Signalement::factory()->create([
            'user_id'           => $admin->id,
            'commentaire'       => 'Commentaire En Attente Unique',
            'statut_validation' => Signalement::STATUT_EN_ATTENTE,
        ]);
        Signalement::factory()->create([
            'user_id'           => $admin->id,
            'commentaire'       => 'Commentaire Valide Unique',
            'statut_validation' => Signalement::STATUT_VALIDE,
        ]);

        // Filtrer par statut=en_attente
        $response = $this->actingAs($admin)->get('/admin/signalements/export?statut=en_attente');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Commentaire En Attente Unique', $content);
        $this->assertStringNotContainsString('Commentaire Valide Unique', $content);
    }

    public function test_neutraliser_cellule_methode_unitaire(): void
    {
        $service = app(ExportSignalementsService::class);

        // Préfixes dangereux préfixés par '
        $this->assertSame("'=SOMME(1+1)", $service->neutraliserCellule('=SOMME(1+1)'));
        $this->assertSame("'+cmd|' /C calc'!A0", $service->neutraliserCellule("+cmd|' /C calc'!A0"));
        $this->assertSame("'-5+2", $service->neutraliserCellule("-5+2"));
        $this->assertSame("'@SUM(1+1)", $service->neutraliserCellule("@SUM(1+1)"));
        $this->assertSame("'\ttabulation", $service->neutraliserCellule("\ttabulation"));
        $this->assertSame("'\rretour", $service->neutraliserCellule("\rretour"));
        $this->assertSame("'\nligne", $service->neutraliserCellule("\nligne"));

        // Valeurs normales non touchées
        $this->assertSame('Normal texte', $service->neutraliserCellule('Normal texte'));
        $this->assertSame('', $service->neutraliserCellule(''));
        $this->assertSame('123', $service->neutraliserCellule('123'));
    }

    public function test_neutralisation_injection_formule_dans_csv(): void
    {
        $admin = $this->createAdmin();

        Signalement::factory()->create([
            'user_id'     => $admin->id,
            'commentaire' => '=SOMME(1+1)',
        ]);

        $response = $this->actingAs($admin)->get('/admin/signalements/export');
        $content = $response->streamedContent();

        $this->assertStringContainsString("'=SOMME(1+1)", $content);
    }

    public function test_echappement_points_virgules_guillemets_et_retours_ligne(): void
    {
        $admin = $this->createAdmin();

        Signalement::factory()->create([
            'user_id'     => $admin->id,
            'commentaire' => "Ligne 1; avec point-virgule\nLigne 2 avec \"guillemets\"",
        ]);

        $response = $this->actingAs($admin)->get('/admin/signalements/export');
        $content = $response->streamedContent();

        // Le champ contenant ';' et retour ligne doit être entouré de guillemets
        $this->assertStringContainsString('"Ligne 1; avec point-virgule', $content);
        $this->assertStringContainsString('Ligne 2 avec ""guillemets"""', $content);
    }

    public function test_absence_de_requete_n_plus_un(): void
    {
        $admin = $this->createAdmin();
        $ressources = RessourceUtile::factory()->count(5)->create([
            'gouvernorat' => 'Bizerte',
        ]);

        foreach ($ressources as $index => $ressource) {
            Signalement::factory()->create([
                'user_id'            => $admin->id,
                'ressource_utile_id' => $ressource->id,
                'statut_validation'  => $index % 2 === 0 ? Signalement::STATUT_VALIDE : Signalement::STATUT_EN_ATTENTE,
            ]);
        }

        DB::enableQueryLog();

        $service = app(ExportSignalementsService::class);
        $temp = fopen('php://memory', 'r+');
        $service->ecrireCsv($temp, []);
        fclose($temp);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Nombre de requêtes attendu :
        // 1. RessourceUtile aggregate (resumeParGouvernorat)
        // 2. Signalement aggregate (resumeParGouvernorat)
        // 3. Signalement non-rattaché aggregate (resumeParGouvernorat)
        // 4. Detail signalements chunkById query
        // 5. PieceJointe count relation chunk
        // 6. User eager load
        // 7. RessourceUtile eager load
        // Total doit être <= 10 requêtes fixes, indépendant du nombre de signalements (O(1))
        $this->assertLessThanOrEqual(10, count($queries), 'Le nombre de requêtes SQL ne doit pas dépendre du nombre de lignes.');
    }
}
