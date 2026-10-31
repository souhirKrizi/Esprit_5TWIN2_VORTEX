<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Mission;
use App\Models\User;
use Database\Seeders\CampagnesMissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampagnesMissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_seeder_can_be_repeated_without_duplicates(): void
    {
        $this->seed(CampagnesMissionsSeeder::class);
        $this->seed(CampagnesMissionsSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('campagnes', 5);
        $this->assertDatabaseCount('missions', 15);

        $agent = User::where('email', 'agent@vortex.com')->firstOrFail();
        $this->assertSame('agent-campagne', $agent->role);
        $this->assertCount(5, $agent->campagnes);

        foreach (Mission::with('campagne')->get() as $mission) {
            $this->assertTrue($mission->date_debut->lt($mission->date_fin));
            $this->assertTrue($mission->date_debut->gte($mission->campagne->date_debut));
            $this->assertTrue($mission->date_fin->lte($mission->campagne->date_fin->copy()->endOfDay()));
        }
    }

    public function test_factories_create_valid_parents_and_respect_campaign_dates(): void
    {
        $mission = Mission::factory()->create();
        $this->assertSame('agent-campagne', $mission->campagne->organisateur->role);
        $this->assertTrue($mission->date_debut->lt($mission->date_fin));

        $campagne = Campagne::factory()->create([
            'date_debut' => '2027-07-10',
            'date_fin' => '2027-07-10',
        ]);
        $mission = Mission::factory()->for($campagne)->create();

        $this->assertSame('2027-07-10', $mission->date_debut->toDateString());
        $this->assertSame('2027-07-10', $mission->date_fin->toDateString());
        $this->assertSame($campagne->lieu, $mission->lieu);
    }
}
