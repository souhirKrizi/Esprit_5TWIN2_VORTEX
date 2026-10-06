<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentMissionCrudTest extends TestCase
{
    use RefreshDatabase;

    private function campagne(User $agent, array $overrides = []): Campagne
    {
        return Campagne::create(array_replace([
            'organisateur_id' => $agent->id, 'titre' => 'Campagne affectée', 'description' => 'Description',
            'objectif' => 'Informer', 'lieu' => 'Tunis', 'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31', 'statut' => 'publiee',
        ], $overrides));
    }

    private function payload(Campagne $campagne, array $overrides = []): array
    {
        return array_replace([
            'campagne_id' => $campagne->id, 'titre' => 'Distribution d’eau', 'description' => 'Préparer les kits.',
            'lieu' => 'Centre-ville', 'date_debut' => '2026-10-12T09:00', 'date_fin' => '2026-10-12T12:00',
            'nombre_places' => 10, 'statut' => 'ouverte',
        ], $overrides);
    }

    private function participation(Mission $mission, string $statut = 'acceptee'): int
    {
        return DB::table('participations')->insertGetId([
            'mission_id' => $mission->id, 'user_id' => User::factory()->create()->id,
            'motivation' => 'Aider les habitants', 'statut' => $statut, 'presence' => 'non_renseignee',
        ]);
    }

    public function test_both_pages_only_show_campaigns_assigned_to_the_signed_in_agent(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $mine = $this->campagne($agent, ['titre' => 'Ma campagne']);
        $other = $this->campagne(User::factory()->create(['role' => 'agent-campagne']), ['titre' => 'Campagne privée autre agent']);
        Mission::create($this->payload($mine, ['titre' => 'Ma mission']));
        Mission::create($this->payload($other, ['titre' => 'Mission privée autre agent']));
        $this->actingAs($agent);
        foreach (['campagnes', 'missions'] as $page) {
            $this->get('/agent-campagne/'.$page)->assertOk()->assertSee('Ma campagne')
                ->assertDontSee('Campagne privée autre agent')->assertDontSee('Mission privée autre agent')
                ->assertViewHas('campagnes', fn ($campagnes) => $campagnes->modelKeys() === [$mine->id])
                ->assertViewHas('totalMissions', 1)->assertSee('data-agent-missions-page', false)
                ->assertDontSee('Données de démonstration');
        }
        $this->get('/agent-campagne/missions')->assertSee('Ma mission');
        $this->get('/agent-campagne/campagnes')->assertSee('/agent-campagne/missions#campagne-'.$mine->id, false);
        $this->assertDatabaseCount('missions', 2);
    }

    public function test_upcoming_campaigns_come_first_and_both_campaigns_and_missions_are_grouped_by_state(): void
    {
        $this->travelTo(now('Africa/Tunis')->setDate(2026, 10, 6)->setTime(12, 0));
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $ongoing = $this->campagne($agent, ['titre' => 'Actuelle', 'statut' => 'en_cours']);
        $later = $this->campagne($agent, ['titre' => 'À venir plus tard', 'date_debut' => '2026-10-20']);
        $sooner = $this->campagne($agent, ['titre' => 'À venir bientôt', 'date_debut' => '2026-10-10']);
        $cancelled = $this->campagne($agent, ['titre' => 'Annulée future', 'date_debut' => '2026-10-15', 'statut' => 'annulee']);
        $draft = $this->campagne($agent, ['titre' => 'Brouillon futur', 'date_debut' => '2026-10-11', 'statut' => 'brouillon']);
        $closed = Mission::create($this->payload($ongoing, ['titre' => 'Mission finie', 'statut' => 'terminee']));
        $open = Mission::create($this->payload($ongoing, ['titre' => 'Mission ouverte', 'statut' => 'ouverte']));

        $this->actingAs($agent)->get('/agent-campagne/missions')->assertOk()
            ->assertSeeInOrder(['À venir bientôt', 'À venir plus tard', 'Brouillon futur', 'Actuelle', 'Mission ouverte', 'Mission finie', 'Annulée future'])
            ->assertSee('Ouvertes')->assertSee('Terminées')
            ->assertViewHas('campaignGroups', function ($groups) use ($sooner, $later, $draft, $ongoing, $cancelled) {
                return $groups['À venir']['publiee']->modelKeys() === [$sooner->id, $later->id]
                    && $groups['À venir']['brouillon']->modelKeys() === [$draft->id]
                    && $groups['Autres campagnes']['en_cours']->modelKeys() === [$ongoing->id]
                    && $groups['Autres campagnes']['annulee']->modelKeys() === [$cancelled->id];
            });
        $this->assertSame($ongoing->id, $open->campagne->id);
        $this->assertSame($ongoing->id, $closed->campagne->id);
    }

    public function test_agent_can_create_read_update_and_delete_missions_and_admin_sees_saved_data(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $campagne = $this->campagne($agent);
        $payload = $this->payload($campagne);
        $response = $this->actingAs($agent)->postJson('/agent-campagne/missions', $payload)
            ->assertCreated()->assertJsonPath('campagne_id', $campagne->id);
        $id = $response->json('id');
        $this->getJson('/agent-campagne/missions/'.$id)->assertOk()
            ->assertJsonPath('mission.titre', 'Distribution d’eau')->assertJsonPath('mission.date_debut', $payload['date_debut']);
        $this->get('/agent-campagne/missions')->assertSee('Distribution d’eau');

        $updated = $this->payload($campagne, ['titre' => 'Mission modifiée', 'statut' => 'en_cours', 'nombre_places' => 8]);
        $this->putJson('/agent-campagne/missions/'.$id, $updated)->assertOk()->assertJsonPath('statut', 'en_cours');
        $this->assertDatabaseHas('missions', ['id' => $id, 'titre' => 'Mission modifiée', 'nombre_places' => 8]);
        $this->get('/agent-campagne/missions')->assertSee('Mission modifiée')->assertDontSee('Distribution d’eau');

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/missions')->assertOk()->assertSee('Mission modifiée');
        $this->actingAs($agent)->deleteJson('/agent-campagne/missions/'.$id)->assertOk();
        $this->assertDatabaseMissing('missions', ['id' => $id]);
        $this->assertDatabaseHas('campagnes', ['id' => $campagne->id]);
        $this->getJson('/agent-campagne/missions/'.$id)->assertNotFound();
        $this->putJson('/agent-campagne/missions/'.$id, $updated)->assertNotFound();
        $this->deleteJson('/agent-campagne/missions/'.$id)->assertNotFound();
    }

    public function test_other_agents_cannot_read_modify_delete_or_create_missions_in_someone_elses_campaign(): void
    {
        $owner = User::factory()->create(['role' => 'agent-campagne']);
        $attacker = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = $this->campagne($owner);
        $mission = Mission::create($this->payload($campaign));
        $own = $this->campagne($attacker);
        $this->actingAs($attacker)->getJson('/agent-campagne/missions/'.$mission->id)->assertNotFound();
        $this->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($own))->assertNotFound();
        $this->deleteJson('/agent-campagne/missions/'.$mission->id)->assertNotFound();
        $this->postJson('/agent-campagne/missions', $this->payload($campaign))->assertUnprocessable()->assertJsonValidationErrors('campagne_id');
        $this->assertDatabaseHas('missions', ['id' => $mission->id, 'campagne_id' => $campaign->id]);
        $this->assertDatabaseCount('missions', 1);
    }

    public function test_reassignment_of_campaign_revokes_previous_agents_access_immediately(): void
    {
        $old = User::factory()->create(['role' => 'agent-campagne']);
        $new = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = $this->campagne($old);
        $mission = Mission::create($this->payload($campaign));
        $campaign->update(['organisateur_id' => $new->id]);
        $this->actingAs($old)->get('/agent-campagne/missions')->assertDontSee('Distribution d’eau');
        $this->getJson('/agent-campagne/missions/'.$mission->id)->assertNotFound();
        $this->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($campaign))->assertNotFound();
        $this->deleteJson('/agent-campagne/missions/'.$mission->id)->assertNotFound();
        $this->actingAs($new)->getJson('/agent-campagne/missions/'.$mission->id)->assertOk();
        $this->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($campaign))->assertOk();
    }

    public function test_mission_can_only_be_moved_to_an_assigned_campaign(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $first = $this->campagne($agent);
        $second = $this->campagne($agent);
        $foreign = $this->campagne(User::factory()->create(['role' => 'agent-campagne']));
        $mission = Mission::create($this->payload($first));
        $this->actingAs($agent)->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($foreign))
            ->assertUnprocessable()->assertJsonValidationErrors('campagne_id');
        $this->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($second))->assertOk();
        $this->assertDatabaseHas('missions', ['id' => $mission->id, 'campagne_id' => $second->id]);
    }

    public function test_all_endpoints_require_authentication_and_agent_role(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = $this->campagne($agent);
        $mission = Mission::create($this->payload($campaign));
        foreach ([null, 'user', 'admin'] as $role) {
            if ($role) {
                $this->actingAs(User::factory()->create(['role' => $role]));
            }
            foreach ([['GET', '/agent-campagne/campagnes'], ['GET', '/agent-campagne/missions'],
                ['POST', '/agent-campagne/missions'], ['GET', '/agent-campagne/missions/'.$mission->id],
                ['PUT', '/agent-campagne/missions/'.$mission->id], ['DELETE', '/agent-campagne/missions/'.$mission->id]] as [$method, $url]) {
                $this->json($method, $url, $this->payload($campaign))->assertStatus($role ? 403 : 401);
            }
        }
        $this->assertDatabaseCount('missions', 1);
    }

    public function test_validation_protects_fields_dates_capacity_and_status_on_create_and_update(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = $this->campagne($agent);
        $mission = Mission::create($this->payload($campaign));
        $this->actingAs($agent);
        foreach ([
            [['titre' => '', 'description' => ' ', 'lieu' => ''], ['titre', 'description', 'lieu']],
            [['date_fin' => '2026-10-12T09:00'], ['date_fin']],
            [['date_debut' => '2026-09-30T09:00'], ['date_debut']],
            [['date_fin' => '2026-11-01T09:00'], ['date_fin']],
            [['date_debut' => 'invalid'], ['date_debut']],
            [['nombre_places' => 0], ['nombre_places']],
            [['nombre_places' => 1.5], ['nombre_places']],
            [['statut' => 'invalid'], ['statut']],
            [['campagne_id' => 999999], ['campagne_id']],
            [['titre' => str_repeat('x', 256)], ['titre']],
        ] as [$invalid, $fields]) {
            $data = $this->payload($campaign, $invalid);
            $this->postJson('/agent-campagne/missions', $data)->assertUnprocessable()->assertJsonValidationErrors($fields);
            $this->putJson('/agent-campagne/missions/'.$mission->id, $data)->assertUnprocessable()->assertJsonValidationErrors($fields);
        }
        $this->assertDatabaseCount('missions', 1);
        $this->postJson('/agent-campagne/missions', $this->payload($campaign, ['date_debut' => '2026-10-01T00:00', 'date_fin' => '2026-10-31T23:59']))->assertCreated();
    }

    public function test_participants_are_real_capacity_cannot_drop_below_active_registrations_and_deletion_cleans_only_its_participations(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = $this->campagne($agent);
        $mission = Mission::create($this->payload($campaign));
        $this->participation($mission, 'acceptee');
        $this->participation($mission, 'en_attente');
        $this->participation($mission, 'annulee');
        $other = Mission::create($this->payload($campaign, ['titre' => 'Autre mission']));
        $otherParticipation = $this->participation($other);
        $this->actingAs($agent)->getJson('/agent-campagne/missions/'.$mission->id)->assertOk()
            ->assertJsonCount(3, 'participants')->assertJsonPath('participants.0.motivation', 'Aider les habitants');
        $this->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($campaign, ['nombre_places' => 1]))
            ->assertUnprocessable()->assertJsonValidationErrors('nombre_places');
        $this->putJson('/agent-campagne/missions/'.$mission->id, $this->payload($campaign, ['nombre_places' => 2]))->assertOk();
        $this->deleteJson('/agent-campagne/missions/'.$mission->id)->assertOk();
        $this->assertDatabaseMissing('participations', ['mission_id' => $mission->id]);
        $this->assertDatabaseHas('participations', ['id' => $otherParticipation]);
        $this->assertDatabaseHas('missions', ['id' => $other->id]);
    }
}
