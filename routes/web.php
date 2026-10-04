<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SignupController;

Route::view('/', 'homepage.index')->name('home');
Route::view('/signin', 'signin.index')->name('signin');
Route::view('/signup', 'signup.index')->name('signup');
Route::post('/signup', [SignupController::class, 'store'])->name('signup.store');
Route::view('/dashbiard', 'welcome')->name('dashboard.preview');
Route::view('/dashboard', 'welcome')->name('dashboard');
