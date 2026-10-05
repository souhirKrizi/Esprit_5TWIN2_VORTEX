<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PasswordResetCodeController;
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
    foreach (['user', 'agent-campagne', 'admin'] as $space) {
        Route::get('/'.$space, [SpaceController::class, 'redirect']);
        Route::get('/'.$space.'/{user}', [SpaceController::class, 'show'])
            ->whereNumber('user')
            ->defaults('space', $space)
            ->name('spaces.'.$space);
    }
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
	Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
	Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
	Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
	Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
	Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
	Route::patch('/users/{user}/archive', [AdminUserController::class, 'archive'])->name('users.archive');
});
