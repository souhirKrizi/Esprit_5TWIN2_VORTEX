<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CarteController;
use App\Http\Controllers\PieceJointeController;
use App\Http\Controllers\PasswordResetCodeController;
use App\Http\Controllers\RessourceUtileController;
use App\Http\Controllers\SignalementController;
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

    Route::get('/user/signalements/carte', [CarteController::class, 'index'])->name('user.signalements.carte');
    Route::get('/user/signalements/api/ressources', [CarteController::class, 'ressources'])->name('user.signalements.api.ressources');
    Route::get('/user/ressources/{ressourceUtile}', [RessourceUtileController::class, 'show'])->name('user.ressources.show');
    Route::get('/user/signalements/tous', [SignalementController::class, 'publicIndex'])->name('user.signalements.public.index');
    Route::get('/user/signalements/tous/{signalement}', [SignalementController::class, 'publicShow'])->name('user.signalements.public.show');
    Route::resource('/user/signalements', SignalementController::class)
        ->except(['store'])
        ->names('user.signalements');
    Route::post('/user/signalements', [SignalementController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('user.signalements.store');
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
