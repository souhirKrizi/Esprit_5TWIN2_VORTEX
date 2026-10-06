<?php

use App\Http\Controllers\AdminCampagneController;
use App\Http\Controllers\AdminMissionController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AgentMissionController;
use App\Http\Controllers\PasswordResetCodeController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserCampaignController;
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
    Route::get('/agent-campagne/campagnes', [AgentMissionController::class, 'index'])->name('agent-campagne.campagnes');
    Route::get('/agent-campagne/missions', [AgentMissionController::class, 'index'])->name('agent-campagne.missions');
    Route::post('/agent-campagne/missions', [AgentMissionController::class, 'store'])->name('agent-campagne.missions.store');
    Route::get('/agent-campagne/missions/{mission}', [AgentMissionController::class, 'show'])->name('agent-campagne.missions.show');
    Route::put('/agent-campagne/missions/{mission}', [AgentMissionController::class, 'update'])->name('agent-campagne.missions.update');
    Route::delete('/agent-campagne/missions/{mission}', [AgentMissionController::class, 'destroy'])->name('agent-campagne.missions.destroy');

    Route::get('/user/campagnes', [UserCampaignController::class, 'index'])->name('user.campagnes');
    Route::get('/user/campagnes/{campagne}/missions', [UserCampaignController::class, 'missions'])->name('user.campagnes.missions');
    Route::post('/user/missions/{mission}/participer', [UserCampaignController::class, 'participate'])->name('user.missions.participate');

    foreach (['user', 'agent-campagne', 'admin'] as $space) {
        Route::get('/'.$space, [SpaceController::class, 'redirect']);
        Route::get('/'.$space.'/{user}', [SpaceController::class, 'show'])
            ->whereNumber('user')
            ->defaults('space', $space)
            ->name('spaces.'.$space);
    }
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
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
