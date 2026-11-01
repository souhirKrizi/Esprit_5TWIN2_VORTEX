<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCampaignExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_contains_only_owned_campaigns_and_preserves_csv_text(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $campaign = Campagne::factory()->create([
            'organisateur_id' => $agent->id, 'titre' => 'Été; "Tunis"',
            'description' => "Première ligne\nDeuxième ligne", 'objectif' => '=1+1',
        ]);
        Mission::factory()->create(['campagne_id' => $campaign->id]);
        Campagne::factory()->create(['titre' => 'Campagne privée autre agent']);
        $response = $this->actingAs($agent)->get(route('agent-campagne.campagnes.export', ['organisateur_id' => 999]))
            ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDownload('mes-campagnes-'.now('Africa/Tunis')->format('Y-m-d').'.csv');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $headers = fgetcsv($stream, 0, ';', '"', '');
        $row = fgetcsv($stream, 0, ';', '"', '');
        $this->assertCount(9, $headers);
        $this->assertSame((string) $campaign->id, $row[0]);
        $this->assertSame($campaign->titre, $row[1]);
        $this->assertSame($campaign->description, $row[2]);
        $this->assertSame("'=1+1", $row[3]);
        $this->assertSame('Publiée', $row[7]);
        $this->assertSame('1', $row[8]);
        $this->assertFalse(fgetcsv($stream, 0, ';', '"', ''));
        fclose($stream);
        $this->assertStringNotContainsString('Campagne privée autre agent', $content);
    }

    public function test_empty_export_contains_headers_and_button_is_available_on_campaigns_page(): void
    {
        $agent = User::factory()->create(['role' => 'agent-campagne']);
        $this->actingAs($agent)->get('/agent-campagne/campagnes')->assertOk()
            ->assertSee('Télécharger CSV')->assertSee(route('agent-campagne.campagnes.export'));
        $content = $this->get(route('agent-campagne.campagnes.export'))->assertOk()->streamedContent();
        $this->assertSame(1, substr_count($content, "\n"));
        $this->get('/agent-campagne/missions')->assertOk()->assertDontSee('Télécharger CSV');
    }

    public function test_export_requires_authenticated_agent_role(): void
    {
        $this->get(route('agent-campagne.campagnes.export'))->assertRedirect('/login');
        foreach (['user', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('agent-campagne.campagnes.export'))->assertForbidden();
        }
    }
}
