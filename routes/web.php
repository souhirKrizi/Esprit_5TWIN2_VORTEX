<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'homepage.index')->name('home');
Route::view('/signin', 'signin.index')->name('signin');
Route::view('/signup', 'signup.index')->name('signup');
Route::view('/dashbiard', 'welcome')->name('dashboard.preview');
Route::view('/dashboard', 'welcome')->name('dashboard');
