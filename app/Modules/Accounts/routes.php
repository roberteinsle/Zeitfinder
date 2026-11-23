<?php

use App\Modules\Accounts\Http\Controllers\AccountController;
use App\Modules\Accounts\Http\Controllers\LoginController;
use App\Modules\Accounts\Http\Controllers\LogoutController;
use App\Modules\Accounts\Http\Controllers\MagicLinkController;
use App\Modules\Accounts\Http\Controllers\PasskeyStepUpController;
use App\Modules\Accounts\Http\Controllers\PasswordLoginController;
use App\Modules\Accounts\Http\Controllers\SecurityController;
use App\Support\Tokens;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/anmelden', [LoginController::class, 'show'])->name('login');

        Route::post('/anmelden/link', [MagicLinkController::class, 'store'])
            ->middleware('throttle:magic-link-request')->name('magic-link.request');

        // The token format is checked by the route constraint before any query runs.
        Route::get('/anmelden/link/{token}', [MagicLinkController::class, 'show'])
            ->where('token', Tokens::PATTERN)->middleware('throttle:magic-link-redeem')->name('magic-link.show');
        Route::post('/anmelden/link/{token}', [MagicLinkController::class, 'redeem'])
            ->where('token', Tokens::PATTERN)->middleware('throttle:magic-link-redeem')->name('magic-link.redeem');

        Route::post('/anmelden/passwort', [PasswordLoginController::class, 'store'])
            ->middleware('throttle:password-login')->name('login.password');
        Route::get('/anmelden/zwei-faktor', [PasswordLoginController::class, 'showTwoFactor'])->name('login.two-factor');
        Route::post('/anmelden/zwei-faktor', [PasswordLoginController::class, 'verifyTwoFactor'])
            ->middleware('throttle:two-factor')->name('login.two-factor.verify');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/abmelden', LogoutController::class)->name('logout');

        Route::get('/konto', [AccountController::class, 'show'])->name('account');
        Route::put('/konto/profil', [AccountController::class, 'updateProfile'])->name('account.profile');

        Route::get('/konto/sicherheit', [SecurityController::class, 'show'])->name('account.security');
        Route::post('/konto/passwort', [SecurityController::class, 'startPasswordLogin'])
            ->middleware('throttle:account-security')->name('account.password.start');
        Route::post('/konto/passwort/bestaetigen', [SecurityController::class, 'confirmPasswordLogin'])
            ->middleware('throttle:two-factor')->name('account.password.confirm');
        Route::post('/konto/passwort/abbrechen', [SecurityController::class, 'cancelPasswordLogin'])->name('account.password.cancel');
        Route::delete('/konto/passwort', [SecurityController::class, 'disablePasswordLogin'])->name('account.password.disable');

        Route::get('/konto/passkey-bestaetigen', [PasskeyStepUpController::class, 'show'])->name('passkey.step-up');
    });
});
