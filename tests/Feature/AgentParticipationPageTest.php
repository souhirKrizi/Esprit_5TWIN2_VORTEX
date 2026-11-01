<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentParticipationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_are_grouped_by_owned_campaign_and_deduplicated_across_missions(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = Campagne::factory()->create(['organisateur_id' => $agent->id, 'titre' => 'Campagne de mon agent']);
        $user = User::factory()->create(['name' => 'Bénévole partagé']);
        $first = Mission::factory()->create(['campagne_id' => $campaign->id, 'titre' => 'Première mission']);
        $second = Mission::factory()->create(['campagne_id' => $campaign->id, 'titre' => 'Deuxième mission']);
        Participation::create(['mission_id' => $first->id, 'user_id' => $user->id, 'statut' => 'acceptee', 'presence' => 'present']);
        Participation::create(['mission_id' => $second->id, 'user_id' => $user->id, 'statut' => 'annulee']);
        $foreign = Mission::factory()->create();
        $other = User::factory()->create(['name' => 'Participant confidentiel']);
        Participation::create(['mission_id' => $foreign->id, 'user_id' => $other->id]);

        $response = $this->actingAs($agent)->get(route('agent-campagne.participations', ['user_id' => $other->id]))
            ->assertOk()->assertViewIs('agent.participations.index')
            ->assertSee('Campagne de mon agent')->assertSee('Première mission')->assertSee('Deuxième mission')
            ->assertSee('Acceptée')->assertSee('Annulée')->assertSee('Présent')
            ->assertSee('1 participant(s)')->assertSee('2 inscription(s)')
            ->assertDontSee('Participant confidentiel')->assertDontSee($foreign->campagne->titre)
            ->assertViewHas('campagnes', fn ($campaigns) => $campaigns->modelKeys() === [$campaign->id]);
        $this->assertSame(1, substr_count($response->getContent(), '<h3>Bénévole partagé</h3>'));
    }

    public function test_page_is_restricted_to_authenticated_agents(): void
    {
        $this->get('/agent-campagne/participations')->assertRedirect('/login');
        foreach (['user', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/agent-campagne/participations')->assertForbidden();
        }
    }

    public function test_empty_states_and_campaign_pagination_render(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $this->actingAs($agent)->get('/agent-campagne/participations')->assertOk()->assertSee('Aucune campagne pour le moment');
        Campagne::factory()->count(11)->create(['organisateur_id' => $agent->id]);
        $this->get('/agent-campagne/participations')->assertOk()->assertSee('Aucun participant pour cette campagne')
            ->assertSee('Page 1 sur 2')->assertViewHas('campagnes', fn ($campaigns) => $campaigns->count() === 10 && $campaigns->total() === 11);
        $this->get('/agent-campagne/participations?page=2')->assertOk()->assertSee('Page 2 sur 2')
            ->assertViewHas('campagnes', fn ($campaigns) => $campaigns->count() === 1);
    }

    public function test_sidebar_removes_unused_agent_buttons_and_activates_participants_link(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $response = $this->actingAs($agent)->get('/agent-campagne/participations')->assertOk();
        foreach (['Créer une campagne', 'Créer une mission', 'Inscriptions à traiter', 'Alertes en cours', 'Ressources et incidents'] as $label) {
            $response->assertDontSee($label);
        }
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $link = (new \DOMXPath($document))->query('//nav//a[@href="'.route('agent-campagne.participations').'"]')->item(0);
        $this->assertNotNull($link);
        $this->assertSame('page', $link->getAttribute('aria-current'));
    }
}
