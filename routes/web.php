<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'homepage.index')->name('home');
Route::view('/dashboard', 'welcome')->name('dashboard');
