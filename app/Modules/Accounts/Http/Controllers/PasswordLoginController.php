<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Models\User;
use App\Modules\Accounts\Actions\CompleteLogin;
use App\Modules\Accounts\Support\TwoFactor;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Email + password, always followed by a TOTP code.
 */
class PasswordLoginController
{
    private const PENDING = 'auth.pending_two_factor';

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => EmailAddress::normalize($request->input('email'))]);
        $validated = $request->validate([
            'email' => EmailAddress::rules(),
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        // Compare against a dummy hash for unknown users so timing does not reveal accounts.
        $hash = $user->password ?? '$2y$12$j/491SQABIIBWUjosFFnDelej8Ki8Zbkb950ru.CnG5xR3WOct7Ym';
        $valid = Hash::check($validated['password'], $hash);

        if (! $valid || $user === null || ! $user->hasPasswordLogin() || $user->isBlocked()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['password' => __('E-Mail oder Passwort ist falsch.')]);
        }

        $request->session()->put(self::PENDING, [
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(5)->getTimestamp(),
        ]);

        return redirect()->route('login.two-factor');
    }

    public function showTwoFactor(Request $request): View|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return view('accounts.two-factor');
    }

    public function verifyTwoFactor(Request $request, TwoFactor $twoFactor, CompleteLogin $login): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login')->withErrors(['password' => __('Bitte melde dich erneut an.')]);
        }

        $code = preg_replace('/\s+/', '', $validated['code']) ?? '';

        if (! $twoFactor->verifyForUser($user, $code)) {
            return back()->withErrors(['code' => __('Der Code ist ungültig.')]);
        }

        $request->session()->forget(self::PENDING);
        $login($request, $user, 'password_totp');

        return redirect()->intended(route('account'));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING);

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        $user = User::query()->find($pending['user_id'] ?? null);

        return $user instanceof User && $user->hasPasswordLogin() && ! $user->isBlocked() ? $user : null;
    }
}
