<?php

namespace Tests\Feature\Prevention;

use App\Models\FicheConseil;
use App\Models\QuestionCitoyen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionCitoyenTest extends TestCase
{
    use RefreshDatabase;

    private function fiche(string $statut = 'publiee'): FicheConseil
    {
        return FicheConseil::create([
            'titre' => 'Bien s’hydrater pendant la canicule',
            'resume' => 'Buvez souvent, même sans avoir soif.',
            'contenu' => str_repeat('Quand il fait très chaud, le corps perd beaucoup d’eau. ', 2),
            'thematique' => 'hydratation',
            'niveau_alerte' => 'jaune',
            'statut' => $statut,
        ]);
    }

    private function question(FicheConseil $fiche, User $auteur, array $attributs = []): QuestionCitoyen
    {
        $question = (new QuestionCitoyen)->forceFill(array_merge(['question' => 'Le thé compte-t-il comme de l’eau ?', 'statut' => 'en_attente'], $attributs));
        $question->fiche_conseil_id = $fiche->id;
        $question->user_id = $auteur->id;
        $question->save();

        return $question;
    }

    public function test_le_citoyen_pose_modifie_et_supprime_sa_question(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $fiche = $this->fiche();

        $this->actingAs($citoyen)->post('/conseils/'.$fiche->id.'/questions', ['question' => 'Peut-on boire de l’eau très froide ?'])
            ->assertRedirect('/conseils/'.$fiche->id)->assertSessionHas('success');
        $question = QuestionCitoyen::firstOrFail();
        $this->assertSame('en_attente', $question->statut);

        $this->actingAs($citoyen)->get('/mes-questions')->assertOk()->assertSee('Peut-on boire de l’eau très froide ?');
        $this->actingAs($citoyen)->get('/mes-questions/'.$question->id.'/modifier')->assertOk();

        $this->actingAs($citoyen)->put('/mes-questions/'.$question->id, ['question' => 'Peut-on boire de l’eau glacée ?'])->assertRedirect('/mes-questions');
        $this->assertSame('Peut-on boire de l’eau glacée ?', $question->fresh()->question);

        $this->actingAs($citoyen)->delete('/mes-questions/'.$question->id)->assertRedirect('/mes-questions');
        $this->assertDatabaseCount('questions_citoyens', 0);
    }

    public function test_mes_questions_sont_regroupees_par_conseil(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $hydratation = $this->fiche();
        $coupure = FicheConseil::create([
            'titre' => 'Se préparer à une coupure de courant',
            'resume' => 'Quelques préparatifs simples avant une coupure.',
            'contenu' => str_repeat('Gardez vos téléphones chargés et une lampe torche. ', 2),
            'thematique' => 'coupure_courant',
            'niveau_alerte' => 'orange',
            'statut' => 'publiee',
        ]);
        $this->question($hydratation, $citoyen, ['question' => 'Première question sur l’eau ?', 'created_at' => now()->subDays(3)]);
        $this->question($coupure, $citoyen, ['question' => 'Question sur la coupure ?', 'created_at' => now()->subDays(2)]);
        $this->question($hydratation, $citoyen, ['question' => 'Deuxième question sur l’eau ?', 'created_at' => now()->subDay()]);

        // Le conseil questionné le plus récemment d'abord, avec toutes ses questions ensemble.
        $this->actingAs($citoyen)->get('/mes-questions')->assertOk()
            ->assertSeeInOrder([
                $hydratation->titre, '2 questions', 'Deuxième question sur l’eau ?', 'Première question sur l’eau ?',
                $coupure->titre, '1 question', 'Question sur la coupure ?',
            ]);
    }

    public function test_controle_de_saisie_de_la_question(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $url = '/conseils/'.$this->fiche()->id.'/questions';

        $this->actingAs($citoyen)->post($url, ['question' => ''])->assertSessionHasErrors(['question' => 'Le champ « question » est obligatoire.']);
        $this->actingAs($citoyen)->post($url, ['question' => 'Court ?'])->assertSessionHasErrors(['question' => 'Le champ « question » doit contenir au moins 10 caractères.']);
        $this->actingAs($citoyen)->post($url, ['question' => str_repeat('a', 501)])->assertSessionHasErrors('question');
        $this->assertDatabaseCount('questions_citoyens', 0);
    }

    public function test_pas_de_question_sur_une_fiche_en_brouillon(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $brouillon = $this->fiche('brouillon');

        $this->actingAs($citoyen)->post('/conseils/'.$brouillon->id.'/questions', ['question' => 'Une question sur un brouillon ?'])->assertNotFound();
    }

    public function test_question_avec_reponse_ou_d_un_autre_non_modifiable(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $voisin = User::factory()->create(['role' => 'user']);
        $fiche = $this->fiche();
        $repondue = $this->question($fiche, $citoyen, ['statut' => 'repondue', 'reponse' => 'Oui, en partie.']);
        $deLAutre = $this->question($fiche, $voisin);

        $this->actingAs($citoyen)->get('/mes-questions/'.$repondue->id.'/modifier')->assertRedirect('/mes-questions')->assertSessionHas('error');
        $this->actingAs($citoyen)->delete('/mes-questions/'.$repondue->id)->assertSessionHas('error');
        $this->assertDatabaseHas('questions_citoyens', ['id' => $repondue->id]);

        $this->actingAs($citoyen)->get('/mes-questions/'.$deLAutre->id.'/modifier')->assertForbidden();
        $this->actingAs($citoyen)->put('/mes-questions/'.$deLAutre->id, ['question' => 'Je change la question du voisin'])->assertForbidden();
        $this->actingAs($citoyen)->delete('/mes-questions/'.$deLAutre->id)->assertForbidden();
    }

    public function test_l_admin_repond_et_la_reponse_s_affiche_sous_le_conseil(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citoyen = User::factory()->create(['role' => 'user']);
        $fiche = $this->fiche();
        $question = $this->question($fiche, $citoyen);

        $this->actingAs($admin)->get('/admin/prevention/questions')->assertOk()->assertSee('Le thé compte-t-il comme de l’eau ?');
        $this->actingAs($admin)->get('/admin/prevention/questions/'.$question->id.'/edit')->assertOk();

        $this->actingAs($admin)->put('/admin/prevention/questions/'.$question->id, ['reponse' => ''])
            ->assertSessionHasErrors(['reponse' => 'Le champ « réponse » est obligatoire.']);
        $this->actingAs($admin)->put('/admin/prevention/questions/'.$question->id, ['reponse' => 'Oui'])
            ->assertSessionHasErrors('reponse');

        // Avant la réponse, la question n'apparaît pas sous le conseil.
        $this->actingAs($citoyen)->get('/conseils/'.$fiche->id)->assertDontSee('Le thé compte-t-il comme de l’eau ?');

        $this->actingAs($admin)->put('/admin/prevention/questions/'.$question->id, ['reponse' => 'Oui, mais buvez surtout de l’eau.'])
            ->assertRedirect('/admin/prevention/questions');
        $this->assertSame('repondue', $question->fresh()->statut);

        $this->actingAs($citoyen)->get('/conseils/'.$fiche->id)->assertSee('Le thé compte-t-il comme de l’eau ?')->assertSee('Oui, mais buvez surtout de l’eau.');
        $this->actingAs($citoyen)->get('/mes-questions')->assertSee('Oui, mais buvez surtout de l’eau.');

        $this->actingAs($admin)->delete('/admin/prevention/questions/'.$question->id)->assertRedirect('/admin/prevention/questions');
        $this->assertDatabaseCount('questions_citoyens', 0);
    }

    public function test_seul_l_admin_repond(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $question = $this->question($this->fiche(), $citoyen);

        $this->actingAs($citoyen)->get('/admin/prevention/questions')->assertForbidden();
        $this->actingAs($citoyen)->put('/admin/prevention/questions/'.$question->id, ['reponse' => 'Je réponds moi-même.'])->assertForbidden();
        $this->assertSame('en_attente', $question->fresh()->statut);
    }

    public function test_la_sidebar_contient_les_liens_du_module(): void
    {
        $citoyen = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($citoyen)->get('/mes-questions')->assertSee('Conseils de prévention')->assertSee('Mes équipements')->assertSee('Mes questions');
        $this->actingAs($admin)->get('/admin/prevention/questions')->assertSee('Fiches conseils')->assertSee('Questions des habitants');
    }
}
