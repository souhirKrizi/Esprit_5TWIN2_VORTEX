<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilUtilisateurTest extends TestCase
{
    use RefreshDatabase;

    private const MOT_DE_PASSE = 'Ancien2026';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M/wHwAEBgIApD5fRAAAAABJRU5ErkJggg==';

    private function citoyen(array $attributs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'governorate' => 'Tunis',
            'password' => self::MOT_DE_PASSE,
        ], $attributs));
    }

    private function photo(string $nom = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nom, base64_decode(self::PNG));
    }

    private function informations(User $user, array $champs = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'governorate' => $user->governorate], $champs);
    }

    public function test_citoyen_et_agent_ouvrent_leur_profil_depuis_la_sidebar(): void
    {
        $citoyen = $this->citoyen(['name' => 'Amira Ben Ali']);
        $agent = $this->citoyen(['role' => 'agent-campagne']);

        $this->actingAs($citoyen)->get('/user/profil')->assertOk()
            ->assertSee('Mes informations')
            ->assertSee('Changer mon mot de passe')
            ->assertSee('Supprimer mon compte')
            ->assertSee('value="Amira Ben Ali"', false)
            ->assertSee('href="'.route('user.profil.edit').'" class="ha-menu-item is-active"', false);

        $this->actingAs($agent)->get('/agent-campagne/profil')->assertOk()
            ->assertSee('href="'.route('agent-campagne.profil.edit').'" class="ha-menu-item is-active"', false);
    }

    public function test_chaque_compte_n_accede_qu_a_son_espace(): void
    {
        $citoyen = $this->citoyen();
        $agent = $this->citoyen(['role' => 'agent-campagne']);
        $admin = $this->citoyen(['role' => 'admin']);

        $this->actingAs($citoyen)->get('/agent-campagne/profil')->assertForbidden();
        $this->actingAs($agent)->get('/user/profil')->assertForbidden();
        $this->actingAs($admin)->get('/user/profil')->assertForbidden();
        $this->actingAs($admin)->get('/agent-campagne/profil')->assertForbidden();
        $this->actingAs($agent)->put('/user/profil', $this->informations($agent))->assertForbidden();
        $this->actingAs($citoyen)->get('/admin/profil')->assertForbidden();
        $this->actingAs($agent)->put('/admin/profil', $this->informations($agent))->assertForbidden();

        auth()->logout();
        $this->get('/user/profil')->assertRedirect('/login');
    }

    public function test_modification_des_informations(): void
    {
        $user = $this->citoyen();

        $this->actingAs($user)->put('/user/profil', [
            'name' => 'Nom Modifié',
            'email' => 'Nouvelle@Exemple.TN',
            'governorate' => 'Sfax',
            // Ces champs ne viennent pas du formulaire et doivent être ignorés.
            'role' => 'admin',
            'archived' => true,
        ])->assertRedirect('/user/profil')->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nom Modifié',
            'email' => 'nouvelle@exemple.tn',
            'governorate' => 'Sfax',
            'role' => 'user',
            'archived' => false,
        ]);
    }

    public function test_controle_de_saisie_des_informations(): void
    {
        $user = $this->citoyen();
        $autre = $this->citoyen();

        $this->actingAs($user)->put('/user/profil', ['name' => 'A', 'email' => 'pas-un-mail', 'governorate' => 'Paris'])
            ->assertSessionHasErrors(['name', 'email', 'governorate']);
        $this->actingAs($user)->put('/user/profil', ['name' => '', 'email' => '', 'governorate' => ''])
            ->assertSessionHasErrors(['name', 'email', 'governorate']);
        $this->actingAs($user)->put('/user/profil', $this->informations($user, ['email' => $autre->email]))
            ->assertSessionHasErrors(['email' => 'Cette adresse e-mail est déjà utilisée par un autre compte.']);

        // Garder sa propre adresse reste possible.
        $this->actingAs($user)->put('/user/profil', $this->informations($user))->assertSessionHasNoErrors();
    }

    public function test_ajout_remplacement_et_suppression_de_la_photo(): void
    {
        Storage::fake('public');
        $user = $this->citoyen();

        $this->actingAs($user)->put('/user/profil', $this->informations($user, ['photo' => $this->photo()]))->assertSessionHasNoErrors();
        $premiere = $user->refresh()->photo;
        Storage::disk('public')->assertExists($premiere);
        $this->actingAs($user)->get('/user/profil')->assertSee('src="'.$user->photoUrl().'"', false);

        $this->actingAs($user)->put('/user/profil', $this->informations($user, ['photo' => $this->photo('autre.png')]))->assertSessionHasNoErrors();
        $seconde = $user->refresh()->photo;
        $this->assertNotSame($premiere, $seconde);
        Storage::disk('public')->assertMissing($premiere);
        Storage::disk('public')->assertExists($seconde);

        // Sans nouveau fichier, la photo ne change pas.
        $this->actingAs($user)->put('/user/profil', $this->informations($user))->assertSessionHasNoErrors();
        $this->assertSame($seconde, $user->refresh()->photo);

        $this->actingAs($user)->put('/user/profil', $this->informations($user, ['supprimer_photo' => '1']))->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->photo);
        Storage::disk('public')->assertMissing($seconde);
    }

    public function test_une_photo_invalide_est_refusee(): void
    {
        Storage::fake('public');
        $user = $this->citoyen();

        $this->actingAs($user)->put('/user/profil', $this->informations($user, [
            'photo' => UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;'),
        ]))->assertSessionHasErrors('photo');

        $this->actingAs($user)->put('/user/profil', $this->informations($user, [
            'photo' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ]))->assertSessionHasErrors('photo');

        $this->actingAs($user)->put('/user/profil', $this->informations($user, [
            'photo' => $this->photo()->size(3000),
        ]))->assertSessionHasErrors(['photo' => 'La photo ne doit pas dépasser 2 Mo.']);

        $this->assertNull($user->refresh()->photo);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_changement_du_mot_de_passe(): void
    {
        $user = $this->citoyen();
        $jeton = $user->remember_token;
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            ['id' => 'autre-appareil', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'autre-compte', 'user_id' => $this->citoyen()->id, 'payload' => '', 'last_activity' => time()],
        ]);

        $this->actingAs($user)->put('/user/profil/mot-de-passe', [
            'current_password' => self::MOT_DE_PASSE,
            'password' => 'Nouveau2026',
            'password_confirmation' => 'Nouveau2026',
        ])->assertRedirect('/user/profil')->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('Nouveau2026', $user->password));
        $this->assertNotSame($jeton, $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'autre-appareil']);
        $this->assertDatabaseHas('sessions', ['id' => 'autre-compte']);
    }

    public function test_controle_de_saisie_du_mot_de_passe(): void
    {
        $user = $this->citoyen();
        $envoyer = fn (array $champs) => $this->actingAs($user)->from('/user/profil')->put('/user/profil/mot-de-passe', $champs);

        $envoyer(['current_password' => 'Mauvais2026', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])
            ->assertRedirect('/user/profil#mot-de-passe')
            ->assertSessionHasErrorsIn('motDePasse', ['current_password' => 'Le mot de passe actuel est incorrect.']);

        foreach (['court1A', 'sansmajuscule1', 'SansChiffre'] as $faible) {
            $envoyer(['current_password' => self::MOT_DE_PASSE, 'password' => $faible, 'password_confirmation' => $faible])
                ->assertSessionHasErrorsIn('motDePasse', 'password');
        }

        $envoyer(['current_password' => self::MOT_DE_PASSE, 'password' => 'Nouveau2026', 'password_confirmation' => 'Different2026'])
            ->assertSessionHasErrorsIn('motDePasse', 'password');
        $this->assertTrue(Hash::check(self::MOT_DE_PASSE, $user->refresh()->password));
    }

    public function test_le_nouveau_mot_de_passe_doit_etre_different(): void
    {
        $user = $this->citoyen();

        $this->actingAs($user)->put('/user/profil/mot-de-passe', [
            'current_password' => self::MOT_DE_PASSE,
            'password' => self::MOT_DE_PASSE,
            'password_confirmation' => self::MOT_DE_PASSE,
        ])->assertSessionHasErrorsIn('motDePasse', ['password' => 'Le nouveau mot de passe doit être différent de l’actuel.']);
    }

    public function test_suppression_du_compte_avec_le_mauvais_mot_de_passe(): void
    {
        $user = $this->citoyen();

        $this->actingAs($user)->from('/user/profil')->delete('/user/profil', ['mot_de_passe' => 'Mauvais2026'])
            ->assertRedirect('/user/profil#suppression')
            ->assertSessionHasErrorsIn('archivage', ['mot_de_passe' => 'Le mot de passe est incorrect.']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'archived' => false]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_la_suppression_archive_le_compte_et_bloque_la_connexion(): void
    {
        Storage::fake('public');
        $agent = $this->citoyen(['role' => 'agent-campagne']);

        $this->actingAs($agent)->delete('/agent-campagne/profil', ['mot_de_passe' => self::MOT_DE_PASSE])
            ->assertRedirect(route('signin'))
            ->assertSessionHas('status');

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $agent->id, 'archived' => true]);

        $this->post('/login', ['email' => $agent->email, 'password' => self::MOT_DE_PASSE])
            ->assertSessionHasErrors(['email' => 'Ce compte est archivé. Contactez l’administrateur pour le récupérer.']);
        $this->assertGuest();
    }

    public function test_le_motif_d_archivage_n_est_pas_donne_sans_le_bon_mot_de_passe(): void
    {
        $user = $this->citoyen(['archived' => true]);

        $erreurs = $this->post('/login', ['email' => $user->email, 'password' => 'Mauvais2026'])
            ->assertSessionHasErrors('email')
            ->getSession()->get('errors')->first('email');

        $this->assertStringNotContainsString('archivé', $erreurs);
        $this->assertGuest();
    }

    public function test_un_compte_archive_par_l_admin_est_deconnecte_puis_peut_etre_restaure(): void
    {
        $user = $this->citoyen();
        $admin = $this->citoyen(['role' => 'admin']);

        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/archive')->assertRedirect();

        $this->actingAs($user->refresh())->get('/user/profil')->assertRedirect(route('signin'));
        $this->assertGuest();

        $this->actingAs($admin)->patch('/admin/users/'.$user->id.'/archive')->assertRedirect();
        auth()->logout();

        $this->post('/login', ['email' => $user->email, 'password' => self::MOT_DE_PASSE])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_la_photo_apparait_dans_la_sidebar(): void
    {
        $sansPhoto = $this->citoyen(['name' => 'Souhir Krizi']);
        $avecPhoto = $this->citoyen(['photo' => 'profils/essai.png']);

        $this->actingAs($sansPhoto)->get('/user/profil')->assertDontSee('>SO<', false)->assertDontSee('<img src="'.asset('storage/'), false);
        $this->actingAs($avecPhoto)->get('/user/profil')->assertSee('<img src="'.asset('storage/profils/essai.png').'" alt="">', false);
    }

    public function test_l_admin_gere_son_profil_mais_ne_peut_pas_le_supprimer(): void
    {
        Storage::fake('public');
        $admin = $this->citoyen(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/profil')->assertOk()
            ->assertSee('Mes informations')
            ->assertSee('Changer mon mot de passe')
            ->assertDontSee('Supprimer mon compte')
            ->assertSee('href="'.route('admin.profil.edit').'" class="ha-menu-item is-active"', false);

        $this->actingAs($admin)->put('/admin/profil', $this->informations($admin, ['name' => 'Admin Modifié', 'photo' => $this->photo()]))
            ->assertRedirect('/admin/profil')->assertSessionHasNoErrors();
        $admin->refresh();
        $this->assertSame('Admin Modifié', $admin->name);
        Storage::disk('public')->assertExists($admin->photo);

        $this->actingAs($admin)->put('/admin/profil/mot-de-passe', [
            'current_password' => self::MOT_DE_PASSE,
            'password' => 'Nouveau2026',
            'password_confirmation' => 'Nouveau2026',
        ])->assertRedirect('/admin/profil')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Nouveau2026', $admin->refresh()->password));

        $this->actingAs($admin)->delete('/admin/profil', ['mot_de_passe' => 'Nouveau2026'])->assertMethodNotAllowed();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'archived' => false]);
    }

    public function test_la_liste_des_comptes_affiche_les_photos_sans_initiales(): void
    {
        $admin = $this->citoyen(['role' => 'admin', 'name' => 'Admin Principal']);
        $this->citoyen(['name' => 'Souhir Krizi', 'photo' => 'profils/souhir.png']);
        $this->citoyen(['name' => 'Ayoub Belhmeid']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()
            ->assertSee('<img src="'.asset('storage/profils/souhir.png').'" alt="">', false)
            ->assertDontSee('>SO<', false)
            ->assertDontSee('>AY<', false)
            ->assertDontSee('>AD<', false);
    }
}
