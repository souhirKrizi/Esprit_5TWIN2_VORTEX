<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCampaignParticipationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now('Africa/Tunis')->setDate(2026, 10, 6)->setTime(12, 0));
    }

    private function campagne(array $overrides = []): Campagne
    {
        return Campagne::create(array_replace([
            'organisateur_id' => User::factory()->create(['role' => 'agent-campagne'])->id,
            'titre' => 'Prévention canicule', 'description' => 'Informer les habitants.',
            'objectif' => 'Prévenir les risques.', 'lieu' => 'Tunis',
            'date_debut' => '2026-10-01', 'date_fin' => '2026-10-31', 'statut' => 'publiee',
        ], $overrides));
    }

    private function mission(Campagne $campagne, array $overrides = []): Mission
    {
        return $campagne->missions()->create(array_replace([
            'titre' => 'Distribution d’eau', 'description' => 'Distribuer les kits.', 'lieu' => 'Centre-ville',
            'date_debut' => '2026-10-12 09:00:00', 'date_fin' => '2026-10-12 12:00:00',
            'nombre_places' => 10, 'statut' => 'ouverte',
        ], $overrides));
    }

    private function participateUrl(Mission $mission): string
    {
        return '/user/missions/'.$mission->id.'/participer';
    }

    private function missionsUrl(Campagne $campagne): string
    {
        return '/user/campagnes/'.$campagne->id.'/missions';
    }

    public function test_all_campaigns_are_grouped_by_state_and_campaigns_with_open_missions_come_first(): void
    {
        $closed = $this->campagne(['titre' => 'Publiée sans mission ouverte']);
        $this->mission($closed, ['statut' => 'complete']);
        $ongoing = $this->campagne(['titre' => 'En cours avec mission ouverte', 'statut' => 'en_cours']);
        $this->mission($ongoing);
        $published = $this->campagne(['titre' => 'Publiée avec mission ouverte']);
        $this->mission($published);
        foreach (['brouillon', 'terminee', 'annulee'] as $status) {
            $this->mission($this->campagne(['titre' => 'Campagne '.$status, 'statut' => $status]));
        }
        $response = $this->actingAs(User::factory()->create(['role' => 'user']))->get('/user/campagnes')
            ->assertOk()->assertViewIs('campaigns.index')
            ->assertViewHas('campagnes', fn ($campagnes) => $campagnes->count() === 6)
            ->assertViewHas('campaignGroups', fn ($groups) => $groups['Campagnes avec missions ouvertes']->flatten(1)->count() === 2)
            ->assertSeeInOrder(['Publiée avec mission ouverte', 'En cours avec mission ouverte', 'Publiée sans mission ouverte', 'Campagne brouillon', 'Campagne terminee', 'Campagne annulee'])
            ->assertSee('ha-discovery-card', false)->assertSee('ha-card-art', false)->assertSee('ha-card-body', false)
            ->assertSee('ha-campaign-cards', false)->assertSee('data-missions-dialog', false)
            ->assertDontSee('Données de démonstration')->assertDontSee('demo-user-1');
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(6, $xpath->query('//article[@data-campaign-id]')->length);
        $this->assertSame(0, $xpath->query('//article[@data-campaign-id]//button[contains(text(), "Participer")]')->length);
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_missions_are_ordered_open_first_and_only_the_users_own_participation_is_exposed(): void
    {
        $campaign = $this->campagne();
        $closed = $this->mission($campaign, ['statut' => 'terminee', 'titre' => 'Terminée']);
        $later = $this->mission($campaign, ['date_debut' => '2026-10-13 09:00:00']);
        $earlier = $this->mission($campaign, ['date_debut' => '2026-10-12 09:00:00']);
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['name' => 'Nom confidentiel', 'email' => 'secret@example.com']);
        Participation::create(['mission_id' => $earlier->id, 'user_id' => $other->id, 'motivation' => 'Motivation confidentielle', 'statut' => 'acceptee']);
        Participation::create(['mission_id' => $later->id, 'user_id' => $user->id, 'statut' => 'en_attente']);

        $this->actingAs($user)->getJson($this->missionsUrl($campaign))->assertOk()
            ->assertJsonCount(3, 'missions')->assertJsonPath('missions.0.id', $earlier->id)
            ->assertJsonPath('missions.1.id', $later->id)->assertJsonPath('missions.2.id', $closed->id)
            ->assertJsonPath('missions.0.available', 9)->assertJsonPath('missions.0.can_participate', true)
            ->assertJsonPath('missions.0.participation_statut', null)
            ->assertJsonPath('missions.1.participation_statut', 'en_attente')->assertJsonPath('missions.1.can_participate', false)
            ->assertJsonMissing(['name' => $other->name])->assertDontSee('secret@example.com')->assertDontSee('Motivation confidentielle');
    }

    public function test_participating_saves_authenticated_user_in_mission_and_agent_can_see_it(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign);
        $user = User::factory()->create(['role' => 'user', 'name' => 'Bénévole inscrit']);
        $other = User::factory()->create();
        $this->actingAs($user)->postJson($this->participateUrl($mission), [
            'user_id' => $other->id, 'mission_id' => 99999, 'statut' => 'acceptee', 'presence' => 'present',
            'motivation' => 'Je veux aider.',
        ])->assertCreated()->assertJsonPath('participation.user_id', $user->id)
            ->assertJsonPath('participation.mission_id', $mission->id)->assertJsonPath('participation.statut', 'acceptee');
        $this->assertDatabaseHas('participations', [
            'mission_id' => $mission->id, 'user_id' => $user->id, 'statut' => 'acceptee',
            'presence' => 'non_renseignee', 'motivation' => 'Je veux aider.',
        ]);
        $this->assertDatabaseMissing('participations', ['user_id' => $other->id]);
        $this->getJson($this->missionsUrl($campaign))->assertJsonPath('missions.0.can_participate', false)
            ->assertJsonPath('missions.0.available', 9)->assertJsonPath('missions.0.participation_statut', 'acceptee');
        $this->actingAs($campaign->organisateur)->getJson('/agent-campagne/missions/'.$mission->id)
            ->assertOk()->assertJsonCount(1, 'participants')->assertJsonPath('participants.0.name', 'Bénévole inscrit');
    }

    public function test_repeated_clicks_are_idempotent_and_do_not_overwrite_an_accepted_registration(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign, ['nombre_places' => 1]);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->postJson($this->participateUrl($mission))->assertCreated();
        $this->postJson($this->participateUrl($mission))->assertOk();
        $participation = Participation::first();
        $participation->update(['statut' => 'acceptee']);
        $this->postJson($this->participateUrl($mission))->assertOk()->assertJsonPath('participation.statut', 'acceptee');
        $this->assertDatabaseCount('participations', 1);
        $this->assertSame($mission->id, $participation->mission->id);
        $this->assertSame($user->id, $participation->user->id);
    }

    public function test_last_place_is_reserved_and_a_second_user_cannot_exceed_capacity(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign, ['nombre_places' => 1]);
        $this->actingAs(User::factory()->create(['role' => 'user']))->postJson($this->participateUrl($mission))->assertCreated();
        $this->actingAs(User::factory()->create(['role' => 'user']))->postJson($this->participateUrl($mission))
            ->assertUnprocessable()->assertJsonValidationErrors('mission');
        $this->getJson($this->missionsUrl($campaign))->assertJsonPath('missions.0.available', 0)->assertJsonPath('missions.0.can_participate', false);
        $this->get('/user/campagnes')->assertViewHas('campaignGroups', fn ($groups) => $groups['Campagnes avec missions ouvertes']->isEmpty());
        $this->assertDatabaseCount('participations', 1);
    }

    public function test_cancelled_and_refused_registrations_do_not_consume_places_and_can_be_reused(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign, ['nombre_places' => 1]);
        foreach (['annulee', 'refusee'] as $status) {
            $user = User::factory()->create(['role' => 'user']);
            $record = Participation::create(['mission_id' => $mission->id, 'user_id' => $user->id, 'statut' => $status]);
            $this->actingAs($user)->getJson($this->missionsUrl($campaign))->assertJsonPath('missions.0.available', 1)
                ->assertJsonPath('missions.0.can_participate', true);
            $this->postJson($this->participateUrl($mission))->assertCreated()->assertJsonPath('participation.id', $record->id)
                ->assertJsonPath('participation.statut', 'acceptee');
            $this->assertSame(1, Participation::where('user_id', $user->id)->count());
            $record->refresh()->update(['statut' => 'annulee']);
        }
    }

    public function test_closed_missions_inactive_campaigns_and_past_missions_cannot_be_joined(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        foreach (['complete', 'en_cours', 'terminee', 'annulee'] as $status) {
            $campaign = $this->campagne();
            $mission = $this->mission($campaign, ['statut' => $status]);
            $this->postJson($this->participateUrl($mission))->assertUnprocessable()->assertJsonValidationErrors('mission');
            $this->getJson($this->missionsUrl($campaign))->assertJsonPath('missions.0.can_participate', false);
        }
        foreach (['brouillon', 'terminee', 'annulee'] as $status) {
            $campaign = $this->campagne(['statut' => $status]);
            $mission = $this->mission($campaign);
            $this->postJson($this->participateUrl($mission))->assertUnprocessable();
            $this->getJson($this->missionsUrl($campaign))->assertJsonPath('missions.0.can_participate', false);
        }
        $campaign = $this->campagne();
        foreach (['2026-10-05 12:00:00', '2026-10-06 12:00:00'] as $start) {
            $mission = $this->mission($campaign, ['date_debut' => $start]);
            $this->postJson($this->participateUrl($mission))->assertUnprocessable();
        }
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_guests_admins_and_agents_cannot_access_user_endpoints(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign);
        foreach ([null, 'admin', 'agent-campagne'] as $role) {
            if ($role) {
                $this->actingAs(User::factory()->create(['role' => $role]));
            }
            foreach ([['GET', '/user/campagnes'], ['GET', $this->missionsUrl($campaign)], ['POST', $this->participateUrl($mission)]] as [$method, $url]) {
                $this->json($method, $url)->assertStatus($role ? 403 : 401);
            }
        }
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_missing_records_and_invalid_motivation_are_rejected_and_empty_states_render(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/user/campagnes')->assertOk()->assertSee('Aucune campagne pour le moment.');
        $this->getJson('/user/campagnes/99999/missions')->assertNotFound();
        $this->postJson('/user/missions/99999/participer')->assertNotFound();
        $campaign = $this->campagne();
        $this->getJson($this->missionsUrl($campaign))->assertOk()->assertJsonCount(0, 'missions');
        $mission = $this->mission($campaign);
        $this->postJson($this->participateUrl($mission), ['motivation' => str_repeat('x', 2001)])
            ->assertUnprocessable()->assertJsonValidationErrors('motivation');
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_my_participations_lists_only_the_signed_in_users_registered_missions(): void
    {
        $campaign = $this->campagne();
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $later = $this->mission($campaign, ['titre' => 'Ma mission suivante', 'date_debut' => '2026-10-13 09:00:00']);
        $earlier = $this->mission($campaign, ['titre' => 'Ma première mission', 'statut' => 'en_cours']);
        $foreign = $this->mission($campaign, ['titre' => 'Mission autre utilisateur']);
        $notRegistered = $this->mission($campaign, ['titre' => 'Mission sans inscription']);
        foreach ([$later, $earlier] as $mission) {
            Participation::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'statut' => 'acceptee']);
        }
        Participation::create(['user_id' => $other->id, 'mission_id' => $foreign->id, 'statut' => 'acceptee']);
        $this->actingAs($user)->get('/user/participations?user_id='.$other->id)->assertOk()
            ->assertViewIs('user.participations.index')
            ->assertViewHas('participations', fn ($participations) => $participations->pluck('mission_id')->all() === [$earlier->id, $later->id])
            ->assertSeeInOrder(['Ma première mission', 'Ma mission suivante'])
            ->assertSee('Participation acceptée')->assertSee('Prévention canicule')
            ->assertSee('Centre-ville')->assertSee('12/10/2026 à 09:00')
            ->assertDontSee($foreign->titre)->assertDontSee($notRegistered->titre)
            ->assertSee('ha-discovery-card', false)->assertSee('ha-card-body', false);
        $this->assertDatabaseCount('participations', 3);
    }

    public function test_clicking_participate_immediately_adds_an_accepted_mission_to_my_participations(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign);
        $this->actingAs(User::factory()->create(['role' => 'user']))->postJson($this->participateUrl($mission))
            ->assertCreated()->assertJsonPath('participation.statut', 'acceptee')
            ->assertDontSee('en attente de validation');
        $this->get('/user/participations')->assertOk()->assertSee($mission->titre)->assertSee('Participation acceptée');
        $this->actingAs($campaign->organisateur)->getJson('/agent-campagne/missions/'.$mission->id)
            ->assertOk()->assertJsonPath('participants.0.statut', 'acceptee');
    }

    public function test_existing_pending_registration_is_accepted_on_click_without_a_duplicate(): void
    {
        $campaign = $this->campagne();
        $mission = $this->mission($campaign, ['nombre_places' => 1]);
        $user = User::factory()->create(['role' => 'user']);
        $participation = Participation::create(['user_id' => $user->id, 'mission_id' => $mission->id, 'statut' => 'en_attente']);
        $this->actingAs($user)->postJson($this->participateUrl($mission))->assertOk()
            ->assertJsonPath('participation.id', $participation->id)->assertJsonPath('participation.statut', 'acceptee');
        $this->assertDatabaseCount('participations', 1);
    }

    public function test_my_participations_requires_user_role_and_has_an_empty_state(): void
    {
        $this->get('/user/participations')->assertRedirect('/login');
        foreach (['admin', 'agent-campagne'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/user/participations')->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/user/participations')->assertOk()
            ->assertSee('Aucune participation pour le moment')->assertViewHas('participations', fn ($participations) => $participations->isEmpty());
        $this->assertDatabaseCount('participations', 0);
    }

    public function test_user_sidebar_links_to_my_participations_and_marks_the_page_active(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/user/campagnes')
            ->assertOk()->assertSee(route('user.participations'));
        $response = $this->get('/user/participations')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $link = (new \DOMXPath($document))->query('//nav//a[@href="'.route('user.participations').'"]')->item(0);
        $this->assertNotNull($link);
        $this->assertSame('page', $link->getAttribute('aria-current'));
        $this->assertStringContainsString('is-active', $link->getAttribute('class'));
    }
}
