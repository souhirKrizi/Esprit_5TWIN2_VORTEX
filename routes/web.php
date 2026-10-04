<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'homepage.index')->name('home');
Route::view('/signin', 'signin.index')->name('signin');
Route::view('/signup', 'signup.index')->name('signup');
Route::view('/dashbiard', 'welcome')->name('dashboard.preview');
Route::view('/dashboard', 'welcome')->name('dashboard');

Route::view('/user', 'spaces.index', ['space' => 'user'])->name('spaces.user');
Route::view('/agent-campagne', 'spaces.index', ['space' => 'agent-campagne'])->name('spaces.agent-campagne');
Route::view('/admin', 'spaces.index', ['space' => 'admin'])->name('spaces.admin');
