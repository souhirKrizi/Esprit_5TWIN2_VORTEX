<?php

use App\Http\Controllers\AdminCampagneController;
use App\Http\Controllers\AdminMissionController;
use App\Http\Controllers\AdminSignalementController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AgentMissionController;
use App\Http\Controllers\AssistantRedactionController;
use App\Http\Controllers\CarteController;
use App\Http\Controllers\Canicule\AdminAlerteController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Canicule\AdminCarteCaniculeController;
use App\Http\Controllers\Canicule\AdminReleveMeteoController;
use App\Http\Controllers\Canicule\AdminZoneController;
use App\Http\Controllers\Canicule\CitoyenCaniculeController;
use App\Http\Controllers\PasswordResetCodeController;
use App\Http\Controllers\Prevention\Admin\FicheConseilController as AdminFicheConseilController;
use App\Http\Controllers\Prevention\Admin\GesteCleController as AdminGesteCleController;
use App\Http\Controllers\Prevention\Admin\QuestionCitoyenController as AdminQuestionCitoyenController;
use App\Http\Controllers\Prevention\EquipementSensibleController;
use App\Http\Controllers\Prevention\FicheConseilController;
use App\Http\Controllers\Prevention\QuestionCitoyenController;
use App\Http\Controllers\PieceJointeController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RelancerAnalyseImageController;
use App\Http\Controllers\RessourceUtileController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserCampaignController;
use App\Http\Controllers\VerificationDoublonController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'homepage.index')->name('home');
Route::view('/signin', 'signin.index')->middleware('guest')->name('signin');
Route::view('/signup', 'signup.index')->middleware('guest')->name('signup');
Route::view('/dashbiard', 'welcome')->name('dashboard.preview');
Route::view('/dashboard', 'welcome')->name('dashboard');

Route::middleware('guest')->controller(PasswordResetCodeController::class)->group(function () {
	Route::get('/forgot-password', 'create')->name('password.request');
	Route::post('/forgot-password', 'store')->name('password.email');
	Route::get('/reset-password', 'edit')->name('password.reset');
	Route::post('/reset-password', 'update')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/agent-campagne/campagnes/{campagne}/rapport', [AgentMissionController::class, 'rapport'])->middleware('throttle:3,1')->name('agent-campagne.campagnes.rapport');
    Route::get('/user/canicule/ma-zone', [CitoyenCaniculeController::class, 'meteo'])->name('user.canicule.meteo');
    Route::get('/user/canicule/alertes', [CitoyenCaniculeController::class, 'alertes'])->name('user.canicule.alertes');
    Route::get('/user/canicule/carte', [CitoyenCaniculeController::class, 'carte'])->name('user.canicule.carte');

    Route::get('/agent-campagne/campagnes', [AgentMissionController::class, 'index'])->name('agent-campagne.campagnes');
    Route::get('/agent-campagne/missions', [AgentMissionController::class, 'index'])->name('agent-campagne.missions');
    Route::post('/agent-campagne/missions', [AgentMissionController::class, 'store'])->name('agent-campagne.missions.store');
    Route::get('/agent-campagne/missions/{mission}', [AgentMissionController::class, 'show'])->name('agent-campagne.missions.show');
    Route::put('/agent-campagne/missions/{mission}', [AgentMissionController::class, 'update'])->name('agent-campagne.missions.update');
    Route::delete('/agent-campagne/missions/{mission}', [AgentMissionController::class, 'destroy'])->name('agent-campagne.missions.destroy');

    Route::get('/user/campagnes', [UserCampaignController::class, 'index'])->name('user.campagnes');
    Route::get('/user/participations', [UserCampaignController::class, 'participations'])->name('user.participations');
    Route::get('/user/campagnes/{campagne}/missions', [UserCampaignController::class, 'missions'])->name('user.campagnes.missions');
    Route::post('/user/missions/{mission}/participer', [UserCampaignController::class, 'participate'])->name('user.missions.participate');

    Route::get('/user/evenements', [EventController::class, 'index'])->name('user.evenements.index');
    Route::post('/user/evenements/{event}/analyser', [EventController::class, 'analyze'])->name('user.evenements.analyse');

    Route::get('/user/signalements/carte', [CarteController::class, 'index'])->name('user.signalements.carte');
    Route::get('/user/signalements/api/ressources', [CarteController::class, 'ressources'])->name('user.signalements.api.ressources');
    Route::get('/user/ressources/{ressourceUtile}', [RessourceUtileController::class, 'show'])->name('user.ressources.show');
    Route::get('/user/signalements/tous', [SignalementController::class, 'publicIndex'])->name('user.signalements.public.index');
    Route::get('/user/signalements/tous/{signalement}', [SignalementController::class, 'publicShow'])->name('user.signalements.public.show');
    Route::post('/user/signalements/assistant-redaction', AssistantRedactionController::class)->middleware('throttle:10,1')->name('user.signalements.assistant-redaction');
    Route::post('/user/signalements/verifier-doublon', VerificationDoublonController::class)->middleware('throttle:30,1')->name('user.signalements.verifier-doublon');
    Route::post('/user/signalements/{signalement}/analyse-image', RelancerAnalyseImageController::class)->middleware('throttle:5,1')->name('user.signalements.analyse-image');
    Route::resource('/user/signalements', SignalementController::class)->except(['store'])->names('user.signalements');
    Route::post('/user/signalements', [SignalementController::class, 'store'])->middleware('throttle:10,1')->name('user.signalements.store');
    Route::delete('/user/pieces-jointes/{pieceJointe}', [PieceJointeController::class, 'destroy'])->name('user.pieces-jointes.destroy');

    foreach (['user', 'agent-campagne', 'admin'] as $space) {
        Route::get('/'.$space, [SpaceController::class, 'redirect']);
        Route::get('/'.$space.'/{user}', [SpaceController::class, 'show'])
            ->whereNumber('user')
            ->defaults('space', $space)
            ->name('spaces.'.$space);
    }
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/canicule/carte', [AdminCarteCaniculeController::class, 'index'])->name('canicule.carte');
    Route::resource('/canicule/zones', AdminZoneController::class)->only(['index', 'edit', 'update'])->names('canicule.zones')->whereNumber('zone');
    Route::resource('/canicule/releves', AdminReleveMeteoController::class)->except(['show'])->parameters(['releves' => 'releve'])->names('canicule.releves')->whereNumber('releve');
    Route::resource('/canicule/alertes', AdminAlerteController::class)->except(['show'])->names('canicule.alertes')->whereNumber('alerte');

    Route::get('/signalements/carte', [AdminSignalementController::class, 'carte'])->name('signalements.carte');
    Route::get('/signalements/api/ressources', [AdminSignalementController::class, 'ressources'])->name('signalements.api.ressources');
    Route::get('/signalements/export', [AdminSignalementController::class, 'export'])->middleware('throttle:10,1')->name('signalements.export');
    Route::get('/signalements', [AdminSignalementController::class, 'index'])->name('signalements.index');
    Route::get('/signalements/{signalement}', [AdminSignalementController::class, 'show'])->name('signalements.show');
    Route::match(['POST', 'PATCH'], '/signalements/{signalement}/valider', [AdminSignalementController::class, 'validateSignalement'])->name('signalements.validate');
    Route::match(['POST', 'PATCH'], '/signalements/{signalement}/rejeter', [AdminSignalementController::class, 'reject'])->name('signalements.reject');

    Route::get('/missions', [AdminMissionController::class, 'index'])->name('missions.index');

    Route::get('/campagne', [AdminCampagneController::class, 'index'])->name('campagnes.index');
    Route::post('/campagne', [AdminCampagneController::class, 'store'])->name('campagnes.store');
    Route::get('/campagne/{campagne}', [AdminCampagneController::class, 'show'])->name('campagnes.show');
    Route::put('/campagne/{campagne}', [AdminCampagneController::class, 'update'])->name('campagnes.update');
    Route::delete('/campagne/{campagne}', [AdminCampagneController::class, 'destroy'])->name('campagnes.destroy');

	Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
	Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
	Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
	Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
	Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
	Route::patch('/users/{user}/archive', [AdminUserController::class, 'archive'])->name('users.archive');
});

// « Mon profil » de chaque espace (rôle vérifié dans ProfilController). L'administrateur ne peut pas supprimer son propre compte.
foreach (['user', 'agent-campagne', 'admin'] as $space) {
    Route::middleware('auth')->prefix($space.'/profil')->name($space.'.profil.')->controller(ProfilController::class)->group(function () use ($space) {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::put('/mot-de-passe', 'updatePassword')->middleware('throttle:6,1')->name('password');

        if ($space !== 'admin') {
            Route::delete('/', 'destroy')->middleware('throttle:6,1')->name('destroy');
        }
    });
}

// Module « Conseils de prévention » — espace citoyen : /user/... (tous les comptes connectés).
Route::middleware('auth')->prefix('user')->name('prevention.')->group(function () {
    Route::get('/conseils', [FicheConseilController::class, 'index'])->name('fiches.index');
    Route::get('/conseils/{fiche}', [FicheConseilController::class, 'show'])->whereNumber('fiche')->name('fiches.show');

    Route::post('/conseils/{fiche}/questions', [QuestionCitoyenController::class, 'store'])->whereNumber('fiche')->name('questions.store');
    Route::get('/mes-questions', [QuestionCitoyenController::class, 'index'])->name('questions.index');
    Route::get('/mes-questions/{question}/modifier', [QuestionCitoyenController::class, 'edit'])->whereNumber('question')->name('questions.edit');
    Route::put('/mes-questions/{question}', [QuestionCitoyenController::class, 'update'])->whereNumber('question')->name('questions.update');
    Route::delete('/mes-questions/{question}', [QuestionCitoyenController::class, 'destroy'])->whereNumber('question')->name('questions.destroy');

    Route::post('/mes-equipements/{equipement}/conseils-ia', [EquipementSensibleController::class, 'genererConseils'])->whereNumber('equipement')->name('equipements.conseils');

    Route::resource('mes-equipements', EquipementSensibleController::class)
        ->parameters(['mes-equipements' => 'equipement'])
        ->names('equipements')
        ->whereNumber('equipement');
});

// Module « Conseils de prévention » — administration : /admin/... (rôle admin vérifié dans AdminPreventionController).
Route::middleware('auth')->prefix('admin')->name('admin.prevention.')->group(function () {
    Route::put('/conseils/alerte-en-cours', [AdminFicheConseilController::class, 'changerAlerte'])->name('alerte.update');

    Route::resource('conseils', AdminFicheConseilController::class)
        ->parameters(['conseils' => 'fiche'])
        ->names('fiches')
        ->whereNumber('fiche');

    Route::post('/conseils/{fiche}/gestes', [AdminGesteCleController::class, 'store'])->whereNumber('fiche')->name('gestes.store');
    Route::post('/conseils/{fiche}/gestes/propositions', [AdminGesteCleController::class, 'proposer'])->whereNumber('fiche')->name('gestes.proposer');
    Route::post('/conseils/{fiche}/gestes/propositions/ajouter', [AdminGesteCleController::class, 'ajouterProposes'])->whereNumber('fiche')->name('gestes.proposes.ajouter');
    Route::delete('/conseils/{fiche}/gestes/propositions', [AdminGesteCleController::class, 'ignorerProposes'])->whereNumber('fiche')->name('gestes.proposes.ignorer');
    Route::get('/gestes/{geste}/edit', [AdminGesteCleController::class, 'edit'])->whereNumber('geste')->name('gestes.edit');
    Route::put('/gestes/{geste}', [AdminGesteCleController::class, 'update'])->whereNumber('geste')->name('gestes.update');
    Route::delete('/gestes/{geste}', [AdminGesteCleController::class, 'destroy'])->whereNumber('geste')->name('gestes.destroy');

    Route::get('/questions', [AdminQuestionCitoyenController::class, 'index'])->name('questions.index');
    Route::get('/questions/{question}/edit', [AdminQuestionCitoyenController::class, 'edit'])->whereNumber('question')->name('questions.edit');
    Route::put('/questions/{question}', [AdminQuestionCitoyenController::class, 'update'])->whereNumber('question')->name('questions.update');
    Route::delete('/questions/{question}', [AdminQuestionCitoyenController::class, 'destroy'])->whereNumber('question')->name('questions.destroy');
});
