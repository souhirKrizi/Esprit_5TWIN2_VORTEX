<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMissionPageTest extends TestCase
{
    use RefreshDatabase;

    private function campagne(array $attributes = []): Campagne
    {
        return Campagne::create(array_replace([
            'organisateur_id' => User::factory()->create(['role' => 'agent-campagne'])->id,
            'titre' => 'Campagne de prévention', 'description' => 'Informer les habitants.',
            'objectif' => 'Prévenir les risques.', 'lieu' => 'Tunis',
            'date_debut' => '2026-10-01', 'date_fin' => '2026-10-15', 'statut' => 'brouillon',
        ], $attributes));
    }

    private function mission(Campagne $campagne, array $attributes = []): Mission
    {
        return $campagne->missions()->create(array_replace([
            'titre' => 'Distribution d’eau', 'description' => 'Distribuer de l’eau aux habitants.',
            'lieu' => 'Centre-ville', 'date_debut' => '2026-10-02 09:00:00',
            'date_fin' => '2026-10-02 12:00:00', 'nombre_places' => 12, 'statut' => 'ouverte',
        ], $attributes));
    }

    public function test_guests_and_other_roles_cannot_view_admin_missions(): void
    {
        $this->get('/admin/missions')->assertRedirect('/login');
        foreach (['user', 'agent-campagne'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/admin/missions')->assertForbidden();
        }
    }

    public function test_admin_sees_missions_nested_under_their_own_campaigns(): void
    {
        $first = $this->campagne(['titre' => 'Campagne Tunis']);
        $second = $this->campagne(['titre' => 'Campagne Sfax', 'statut' => 'annulee']);
        $later = $this->mission($first, ['titre' => 'Sensibilisation', 'date_debut' => '2026-10-03 09:00:00', 'statut' => 'en_cours']);
        $earlier = $this->mission($first);
        $other = $this->mission($second, ['titre' => 'Visites à Sfax', 'statut' => 'annulee']);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/missions')->assertOk()->assertViewIs('admin.missions.index')
            ->assertViewHas('totalMissions', 3)->assertViewHas('missionsEnCours', 1)
            ->assertSee('Campagne Tunis')->assertSee('Campagne Sfax')
            ->assertSee('Distribution d’eau')->assertSee('Sensibilisation')->assertSee('Visites à Sfax')
            ->assertSee('12 places')->assertSee('02/10/2026 à 09:00')
            ->assertSee('Centre-ville')->assertSee('Distribuer de l’eau aux habitants.')
            ->assertSee($first->organisateur->name)->assertSee($second->organisateur->name)
            ->assertSee('ha-admin-mission-grid', false);

        $response->assertViewHas('campagnes', function ($campagnes) use ($first, $second, $earlier, $later, $other) {
            return $campagnes->count() === 2
                && $campagnes->firstWhere('id', $first->id)->missions->modelKeys() === [$earlier->id, $later->id]
                && $campagnes->firstWhere('id', $second->id)->missions->modelKeys() === [$other->id];
        });

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($document);
        foreach ([[$first, [$earlier, $later]], [$second, [$other]]] as [$campagne, $missions]) {
            $cards = $xpath->query('//section[@data-campaign-id="'.$campagne->id.'"]//article[@data-mission-id]');
            $this->assertSame(count($missions), $cards->length);
            foreach ($missions as $index => $mission) {
                $this->assertSame((string) $mission->id, $cards->item($index)->getAttribute('data-mission-id'));
            }
        }

        $this->assertDatabaseCount('campagnes', 2);
        $this->assertDatabaseCount('missions', 3);
    }

    public function test_campaigns_without_missions_and_empty_database_have_clear_empty_states(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/missions')->assertOk()->assertSee('Aucune campagne pour le moment')
            ->assertViewHas('totalMissions', 0)->assertViewHas('missionsEnCours', 0);

        $campagne = $this->campagne();
        $this->get('/admin/missions')->assertOk()->assertSee($campagne->titre)
            ->assertSee('Aucune mission liée à cette campagne pour le moment.')
            ->assertSee('0 missions liées');
        $this->assertDatabaseCount('missions', 0);
    }

    public function test_all_campaign_and_mission_statuses_are_included_and_text_is_escaped(): void
    {
        foreach (Campagne::STATUTS as $index => $status) {
            $campagne = $this->campagne(['statut' => $status, 'titre' => '<script>campaign()</script>']);
            $this->mission($campagne, ['statut' => Mission::STATUTS[$index], 'description' => '<script>mission()</script>']);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/missions')->assertOk()
            ->assertViewHas('campagnes', fn ($campagnes) => $campagnes->count() === 5)
            ->assertViewHas('totalMissions', 5)
            ->assertSee('&lt;script&gt;campaign()&lt;/script&gt;', false)
            ->assertSee('&lt;script&gt;mission()&lt;/script&gt;', false)
            ->assertDontSee('<script>campaign()</script>', false)
            ->assertDontSee('<script>mission()</script>', false);
    }

    public function test_sidebar_links_to_missions_and_marks_it_active(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/campagne')->assertOk()->assertSee(route('admin.missions.index'));
        $response = $this->get('/admin/missions')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $link = (new \DOMXPath($document))->query('//nav//a[@href="'.route('admin.missions.index').'"]')->item(0);
        $this->assertNotNull($link);
        $this->assertSame('page', $link->getAttribute('aria-current'));
        $this->assertStringContainsString('is-active', $link->getAttribute('class'));
    }
}
