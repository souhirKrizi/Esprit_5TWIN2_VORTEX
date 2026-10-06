<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PasswordResetCodeController;
use App\Http\Controllers\Prevention\Admin\FicheConseilController as AdminFicheConseilController;
use App\Http\Controllers\Prevention\Admin\GesteCleController as AdminGesteCleController;
use App\Http\Controllers\Prevention\Admin\QuestionCitoyenController as AdminQuestionCitoyenController;
use App\Http\Controllers\Prevention\EquipementSensibleController;
use App\Http\Controllers\Prevention\FicheConseilController;
use App\Http\Controllers\Prevention\QuestionCitoyenController;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'homepage.index')->name('home');
Route::view('/signin', 'signin.index')->name('signin');
Route::view('/signup', 'signup.index')->name('signup');
Route::view('/dashbiard', 'welcome')->name('dashboard.preview');
Route::view('/dashboard', 'welcome')->name('dashboard');

Route::middleware('guest')->controller(PasswordResetCodeController::class)->group(function () {
	Route::get('/forgot-password', 'create')->name('password.request');
	Route::post('/forgot-password', 'store')->name('password.email');
	Route::get('/reset-password', 'edit')->name('password.reset');
	Route::post('/reset-password', 'update')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/agent-campagne/missions', function (\Illuminate\Http\Request $request) {
        abort_unless($request->user()->role === 'agent-campagne', 403);

        return view('campaigns.index', ['space' => 'agent-campagne']);
    })->name('agent-campagne.missions');

    Route::get('/user/campagnes', function (\Illuminate\Http\Request $request) {
        abort_unless($request->user()->role === 'user', 403);

        return view('campaigns.index', ['space' => 'user']);
    })->name('user.campagnes');

    foreach (['user', 'agent-campagne', 'admin'] as $space) {
        Route::get('/'.$space, [SpaceController::class, 'redirect']);
        Route::get('/'.$space.'/{user}', [SpaceController::class, 'show'])
            ->whereNumber('user')
            ->defaults('space', $space)
            ->name('spaces.'.$space);
    }
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/campagne', function (\Illuminate\Http\Request $request) {
        abort_unless($request->user()->role === 'admin', 403);

        return view('admin.campagnes.index');
    })->name('campagnes.index');

	Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
	Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
	Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
	Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
	Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
	Route::patch('/users/{user}/archive', [AdminUserController::class, 'archive'])->name('users.archive');
});

// Module « Conseils de prévention » — espace citoyen (tous les comptes connectés).
Route::middleware('auth')->name('prevention.')->group(function () {
    Route::get('/conseils', [FicheConseilController::class, 'index'])->name('fiches.index');
    Route::get('/conseils/{fiche}', [FicheConseilController::class, 'show'])->whereNumber('fiche')->name('fiches.show');

    Route::post('/conseils/{fiche}/questions', [QuestionCitoyenController::class, 'store'])->whereNumber('fiche')->name('questions.store');
    Route::get('/mes-questions', [QuestionCitoyenController::class, 'index'])->name('questions.index');
    Route::get('/mes-questions/{question}/modifier', [QuestionCitoyenController::class, 'edit'])->whereNumber('question')->name('questions.edit');
    Route::put('/mes-questions/{question}', [QuestionCitoyenController::class, 'update'])->whereNumber('question')->name('questions.update');
    Route::delete('/mes-questions/{question}', [QuestionCitoyenController::class, 'destroy'])->whereNumber('question')->name('questions.destroy');

    Route::resource('mes-equipements', EquipementSensibleController::class)
        ->parameters(['mes-equipements' => 'equipement'])
        ->names('equipements')
        ->whereNumber('equipement');
});

// Module « Conseils de prévention » — administration (rôle admin vérifié dans AdminPreventionController).
Route::middleware('auth')->prefix('admin/prevention')->name('admin.prevention.')->group(function () {
    Route::resource('fiches', AdminFicheConseilController::class)
        ->parameters(['fiches' => 'fiche'])
        ->whereNumber('fiche');

    Route::post('/fiches/{fiche}/gestes', [AdminGesteCleController::class, 'store'])->whereNumber('fiche')->name('gestes.store');
    Route::get('/gestes/{geste}/edit', [AdminGesteCleController::class, 'edit'])->whereNumber('geste')->name('gestes.edit');
    Route::put('/gestes/{geste}', [AdminGesteCleController::class, 'update'])->whereNumber('geste')->name('gestes.update');
    Route::delete('/gestes/{geste}', [AdminGesteCleController::class, 'destroy'])->whereNumber('geste')->name('gestes.destroy');

    Route::get('/questions', [AdminQuestionCitoyenController::class, 'index'])->name('questions.index');
    Route::get('/questions/{question}/edit', [AdminQuestionCitoyenController::class, 'edit'])->whereNumber('question')->name('questions.edit');
    Route::put('/questions/{question}', [AdminQuestionCitoyenController::class, 'update'])->whereNumber('question')->name('questions.update');
    Route::delete('/questions/{question}', [AdminQuestionCitoyenController::class, 'destroy'])->whereNumber('question')->name('questions.destroy');
});
