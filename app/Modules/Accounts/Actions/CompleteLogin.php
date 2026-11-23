<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs a user in after a successful magic link or password + TOTP check.
 * Passkey sign-ins are handled by laravel/passkeys.
 */
final class CompleteLogin
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(Request $request, User $user, string $method, bool $remember = false): void
    {
        Auth::login($user, $remember);

        $request->session()->regenerate();
        // Only a passkey proves the stronger factor that the admin area requires.
        $request->session()->forget('auth.passkey_verified_at');
        $request->session()->put('auth.method', $method);

        $this->activity->log('user.login', $user, ['method' => $method], $user->id);
    }
}
