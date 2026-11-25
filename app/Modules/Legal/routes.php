<?php

use App\Modules\Legal\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/impressum', [LegalController::class, 'imprint'])->name('imprint');
    Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('privacy');
});
