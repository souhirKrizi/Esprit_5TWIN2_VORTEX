<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitoyenCaniculeCampaignAdviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_page_removes_legacy_weather_cards_and_lists_campaign_guidance(): void
    {
        $zone = Zone::create([
            'code' => 'TN-11',
            'nom' => 'Tunis',
            'geometrie_geojson' => '{}',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'governorate' => 'Tunis',
        ]);

        $campagne = Campagne::create([
            'titre' => 'Marathon de la ville',
            'description' => 'Course en plein air au centre-ville.',
            'lieu' => 'Ariana',
            'date_debut' => now()->addDays(2),
            'date_fin' => now()->addDays(3),
            'statut' => 'publiee',
            'organisateur_id' => $user->id,
            'objectif' => 'Prévention',
        ]);

        $this->actingAs($user)
            ->get('/user/canicule/ma-zone?zone_id='.$zone->id)
            ->assertOk()
            ->assertDontSee('Dernière observation')
            ->assertDontSee('Dernière prévision')
            ->assertDontSee('Aucune observation enregistrée pour le moment.')
            ->assertDontSee('Aucune prévision enregistrée pour le moment.');

        $this->actingAs($user)
            ->get('/user/canicule/alertes')
            ->assertOk()
            ->assertSee('Campagnes disponibles')
            ->assertSee('Info :')
            ->assertSee('Actualiser')
            ->assertSee('Marathon de la ville');
    }
}
