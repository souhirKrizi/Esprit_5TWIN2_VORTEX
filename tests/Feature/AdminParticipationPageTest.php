<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParticipationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_campaigns_from_all_agents_and_groups_each_participant_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $volunteer = User::factory()->create(['name' => 'Bénévole commun']);
        foreach (['Agent Nord', 'Agent Sud'] as $name) {
            $agent = User::factory()->create(['name' => $name, 'role' => 'agent-campagne']);
            $campaign = Campagne::factory()->create(['organisateur_id' => $agent->id, 'titre' => 'Campagne '.$name]);
            foreach (['Distribution', 'Sensibilisation'] as $title) {
                $mission = Mission::factory()->create(['campagne_id' => $campaign->id, 'titre' => $title.' '.$name]);
                Participation::create(['user_id' => $volunteer->id, 'mission_id' => $mission->id, 'statut' => 'acceptee', 'presence' => 'present']);
            }
        }

        $response = $this->actingAs($admin)->get('/admin/participants')->assertOk()
            ->assertViewIs('admin.participants.index')->assertSee('Participations aux missions')
            ->assertSee('Agent responsable : Agent Nord')->assertSee('Agent responsable : Agent Sud')
            ->assertSee('Distribution Agent Nord')->assertSee('Sensibilisation Agent Sud')
            ->assertSee('Acceptée')->assertSee('Présent')->assertSee('1 participant(s)')
            ->assertSee('2 inscription(s)')->assertViewHas('campagnes', fn ($campaigns) => $campaigns->total() === 2);
        $this->assertSame(2, substr_count($response->getContent(), '<h3>Bénévole commun</h3>'));

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $link = (new \DOMXPath($document))->query('//nav//a[@href="'.route('admin.participants.index').'"]')->item(0);
        $this->assertNotNull($link);
        $this->assertSame('page', $link->getAttribute('aria-current'));
    }

    public function test_only_administrators_can_open_the_page(): void
    {
        $this->get('/admin/participants')->assertRedirect('/login');
        foreach (['user', 'agent-campagne'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/participants')->assertForbidden();
        }
    }

    public function test_empty_states_and_pagination_are_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/participants')->assertOk()->assertSee('Aucune campagne pour le moment');
        Campagne::factory()->count(11)->create();
        $this->get('/admin/participants')->assertOk()->assertSee('Aucun participant pour cette campagne')
            ->assertSee('Page 1 sur 2')->assertViewHas('campagnes', fn ($campaigns) => $campaigns->count() === 10);
        $this->get('/admin/participants?page=2')->assertOk()->assertSee('Page 2 sur 2')
            ->assertViewHas('campagnes', fn ($campaigns) => $campaigns->count() === 1);
    }
}
