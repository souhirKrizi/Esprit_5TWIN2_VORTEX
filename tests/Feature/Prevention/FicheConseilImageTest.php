<?php

namespace Tests\Feature\Prevention;

use App\Models\FicheConseil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FicheConseilImageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'titre' => 'Bien s’hydrater pendant la canicule',
            'resume' => 'Buvez souvent, même sans avoir soif.',
            'contenu' => 'Quand il fait très chaud, le corps perd beaucoup d’eau.',
            'thematique' => 'hydratation',
            'niveau_alerte' => 'jaune',
            'statut' => 'publiee',
        ], $surcharge);
    }

    private function photo(string $nom = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->create($nom, 100, 'image/jpeg');
    }

    private function ficheAvecImage(): FicheConseil
    {
        $this->actingAs($this->admin)->post('/admin/conseils', $this->donnees(['image' => $this->photo()]))->assertSessionHasNoErrors();

        return FicheConseil::firstOrFail();
    }

    public function test_l_admin_ajoute_une_image_visible_par_le_citoyen(): void
    {
        $fiche = $this->ficheAvecImage();
        $citoyen = User::factory()->create(['role' => 'user']);

        Storage::disk('public')->assertExists($fiche->image);
        $this->assertStringStartsWith('fiches/', $fiche->image);

        $this->actingAs($this->admin)->get('/admin/conseils/'.$fiche->id)->assertSee('storage/'.$fiche->image);
        $this->actingAs($citoyen)->get('/user/conseils')->assertSee('storage/'.$fiche->image);
        $this->actingAs($citoyen)->get('/user/conseils/'.$fiche->id)->assertSee('storage/'.$fiche->image);
    }

    public function test_l_image_est_facultative(): void
    {
        $this->actingAs($this->admin)->post('/admin/conseils', $this->donnees())->assertSessionHasNoErrors();

        $this->assertNull(FicheConseil::firstOrFail()->image);
    }

    public function test_controle_de_saisie_de_l_image(): void
    {
        $this->actingAs($this->admin)->post('/admin/conseils', $this->donnees(['image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors(['image' => 'Le fichier doit être une image.']);

        $this->actingAs($this->admin)->post('/admin/conseils', $this->donnees(['image' => UploadedFile::fake()->create('image.gif', 100, 'image/gif')]))
            ->assertSessionHasErrors(['image' => 'L’image doit être au format JPG, PNG ou WEBP.']);

        $this->actingAs($this->admin)->post('/admin/conseils', $this->donnees(['image' => UploadedFile::fake()->create('grande.jpg', 3000, 'image/jpeg')]))
            ->assertSessionHasErrors(['image' => 'L’image ne doit pas dépasser 2 Mo.']);

        $this->assertDatabaseCount('fiches_conseils', 0);
    }

    public function test_remplacer_l_image_supprime_l_ancienne(): void
    {
        $fiche = $this->ficheAvecImage();
        $ancienne = $fiche->image;

        $this->actingAs($this->admin)->put('/admin/conseils/'.$fiche->id, $this->donnees(['image' => $this->photo('nouvelle.jpg')]))->assertSessionHasNoErrors();

        $fiche->refresh();
        $this->assertNotSame($ancienne, $fiche->image);
        Storage::disk('public')->assertMissing($ancienne);
        Storage::disk('public')->assertExists($fiche->image);
    }

    public function test_modifier_sans_nouvelle_image_garde_l_image(): void
    {
        $fiche = $this->ficheAvecImage();
        $image = $fiche->image;

        $this->actingAs($this->admin)->put('/admin/conseils/'.$fiche->id, $this->donnees(['titre' => 'Boire de l’eau quand il fait chaud']))->assertSessionHasNoErrors();

        $this->assertSame($image, $fiche->fresh()->image);
        Storage::disk('public')->assertExists($image);
    }

    public function test_supprimer_l_image_sans_supprimer_la_fiche(): void
    {
        $fiche = $this->ficheAvecImage();
        $image = $fiche->image;

        $this->actingAs($this->admin)->put('/admin/conseils/'.$fiche->id, $this->donnees(['supprimer_image' => '1']))->assertSessionHasNoErrors();

        $this->assertNull($fiche->fresh()->image);
        Storage::disk('public')->assertMissing($image);
    }

    public function test_supprimer_la_fiche_supprime_son_image(): void
    {
        $fiche = $this->ficheAvecImage();
        $image = $fiche->image;

        $this->actingAs($this->admin)->delete('/admin/conseils/'.$fiche->id)->assertRedirect('/admin/conseils');

        Storage::disk('public')->assertMissing($image);
    }
}
