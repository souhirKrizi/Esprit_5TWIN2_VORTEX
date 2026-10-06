<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCampagneCrudTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'organisateur_id' => User::factory()->create(['role' => 'agent-campagne'])->id,
            'titre' => 'Prévention canicule',
            'description' => 'Actions de sensibilisation.',
            'objectif' => 'Informer les habitants.',
            'lieu' => 'Tunis',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-15',
            'statut' => 'brouillon',
        ], $overrides);
    }

    public function test_admin_sees_all_campaigns_and_only_campaign_agents_in_the_selector(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (Campagne::STATUTS as $statut) {
            Campagne::create($this->payload(['statut' => $statut]));
        }
        $citizen = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)->getJson('/admin/campagne')
            ->assertOk()->assertJsonCount(5, 'campagnes')->assertJsonCount(5, 'agents')
            ->assertJsonPath('agents', fn ($agents) => ! in_array($admin->id, array_column($agents, 'id'))
                && ! in_array($citizen->id, array_column($agents, 'id')));

        $this->get('/admin/campagne')->assertOk()
            ->assertViewHas('campagnes', fn ($campagnes) => $campagnes->count() === 5)
            ->assertViewHas('campagnes', fn ($campagnes) => $campagnes->every(fn ($campagne) => $campagne->titre === 'Prévention canicule'))
            ->assertDontSee('demo-agent-1')
            ->assertDontSee('Aperçu avec des données de démonstration.');
    }

    public function test_admin_can_create_read_update_and_delete_a_campaign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = $this->payload();
        $response = $this->actingAs($admin)->postJson('/admin/campagne', $data)
            ->assertCreated()->assertJsonPath('titre', $data['titre'])
            ->assertJsonPath('organisateur.id', $data['organisateur_id']);
        $id = $response->json('id');
        $this->assertDatabaseHas('campagnes', ['id' => $id] + $data);

        $this->getJson('/admin/campagne/'.$id)->assertOk()
            ->assertJsonPath('description', $data['description'])
            ->assertJsonPath('date_debut', '2026-10-01');

        $replacement = User::factory()->create(['role' => 'agent-campagne']);
        $updated = array_replace($data, ['titre' => 'Campagne modifiée', 'statut' => 'publiee', 'organisateur_id' => $replacement->id]);
        $this->putJson('/admin/campagne/'.$id, $updated)->assertOk()
            ->assertJsonPath('organisateur.id', $replacement->id);
        $this->assertDatabaseHas('campagnes', ['id' => $id] + $updated);
        $this->assertSame($id, $replacement->campagnes()->first()->id);

        $this->deleteJson('/admin/campagne/'.$id)->assertOk();
        $this->assertDatabaseMissing('campagnes', ['id' => $id]);
        $this->getJson('/admin/campagne/'.$id)->assertNotFound();
        $this->putJson('/admin/campagne/'.$id, $data)->assertNotFound();
        $this->deleteJson('/admin/campagne/'.$id)->assertNotFound();
    }

    public function test_guests_and_other_roles_cannot_use_any_campaign_endpoint(): void
    {
        $data = $this->payload();
        $campagne = Campagne::create($data);
        foreach ([null, 'user', 'agent-campagne'] as $role) {
            if ($role) {
                $this->actingAs(User::factory()->create(['role' => $role]));
            }
            foreach ([['GET', '/admin/campagne'], ['GET', '/admin/campagne/'.$campagne->id],
                ['POST', '/admin/campagne'], ['PUT', '/admin/campagne/'.$campagne->id],
                ['DELETE', '/admin/campagne/'.$campagne->id]] as [$method, $url]) {
                $this->json($method, $url, $data)->assertStatus($role ? 403 : 401);
            }
        }
        $this->assertDatabaseCount('campagnes', 1);
        $this->assertDatabaseHas('campagnes', ['id' => $campagne->id] + $data);
    }

    public function test_campaign_validation_applies_to_creation_and_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = $this->payload();
        $campagne = Campagne::create($data);
        $this->actingAs($admin);
        $invalidPayloads = [
            [['organisateur_id' => $admin->id], ['organisateur_id']],
            [['organisateur_id' => User::factory()->create(['role' => 'user'])->id], ['organisateur_id']],
            [['organisateur_id' => 999999], ['organisateur_id']],
            [['date_fin' => '2026-09-30'], ['date_fin']],
            [['date_debut' => 'not-a-date'], ['date_debut']],
            [['statut' => 'invalid'], ['statut']],
            [['titre' => ' ', 'description' => '', 'objectif' => '', 'lieu' => ''], ['titre', 'description', 'objectif', 'lieu']],
            [['titre' => str_repeat('a', 256), 'lieu' => str_repeat('b', 256)], ['titre', 'lieu']],
        ];
        foreach ($invalidPayloads as [$invalid, $fields]) {
            $payload = array_replace($data, $invalid);
            $this->postJson('/admin/campagne', $payload)->assertUnprocessable()->assertJsonValidationErrors($fields);
            $this->putJson('/admin/campagne/'.$campagne->id, $payload)->assertUnprocessable()->assertJsonValidationErrors($fields);
        }
        $this->assertDatabaseCount('campagnes', 1);
        $this->assertDatabaseHas('campagnes', ['id' => $campagne->id] + $data);
    }

    public function test_images_are_stored_preserved_replaced_and_removed(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = $this->payload();
        $image = fn () => UploadedFile::fake()->createWithContent('campagne.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        $response = $this->post('/admin/campagne', $data + ['image' => $image()], ['Accept' => 'application/json'])->assertCreated();
        $id = $response->json('id');
        $oldPath = $response->json('image');
        Storage::disk('public')->assertExists($oldPath);

        $this->putJson('/admin/campagne/'.$id, $data + ['image' => null])->assertOk()->assertJsonPath('image', $oldPath);
        Storage::disk('public')->assertExists($oldPath);

        $response = $this->post('/admin/campagne/'.$id, $data + ['_method' => 'PUT', 'image' => $image()], ['Accept' => 'application/json'])->assertOk();
        $newPath = $response->json('image');
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $this->deleteJson('/admin/campagne/'.$id)->assertOk();
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_invalid_images_do_not_create_campaigns_or_files(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/campagne', $this->payload() + ['image' => UploadedFile::fake()->create('document.pdf', 10)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertDatabaseCount('campagnes', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_campaigns_with_missions_cannot_be_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('campagnes/existing.png', 'existing image');
        $campagne = Campagne::create($this->payload(['image' => 'campagnes/existing.png']));
        DB::table('missions')->insert([
            'campagne_id' => $campagne->id, 'titre' => 'Mission', 'description' => 'Description',
            'lieu' => 'Tunis', 'date_debut' => '2026-10-01 09:00:00', 'date_fin' => '2026-10-01 12:00:00',
            'nombre_places' => 10, 'statut' => 'ouverte',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->deleteJson('/admin/campagne/'.$campagne->id)->assertUnprocessable()->assertJsonValidationErrors('campagne');
        $this->assertDatabaseHas('campagnes', ['id' => $campagne->id]);
        $this->assertDatabaseCount('missions', 1);
        Storage::disk('public')->assertExists('campagnes/existing.png');
    }
}
