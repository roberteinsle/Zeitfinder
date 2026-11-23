<?php

use Illuminate\Support\Facades\Route;

// Module routes are loaded by the module service providers (app/Modules/*/routes.php).
Route::view('/', 'home')->name('home');
Route::view('/datenschutz', 'privacy')->name('privacy');
