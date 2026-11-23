<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Models\User;
use App\Modules\Accounts\Actions\DisablePasswordLogin;
use App\Modules\Accounts\Actions\EnablePasswordLogin;
use App\Modules\Accounts\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecurityController
{
    private const PENDING = 'security.pending_password_login';

    public function show(Request $request, TwoFactor $twoFactor): View
    {
        /** @var User $user */
        $user = $request->user();
        $pending = $request->session()->get(self::PENDING);

        return view('accounts.security', [
            'user' => $user,
            'passkeys' => $user->passkeys()->latest()->get(),
            'pendingQr' => is_array($pending) ? $twoFactor->qrCodeSvg($user->email, $pending['secret']) : null,
            'pendingSecret' => is_array($pending) ? $pending['secret'] : null,
        ]);
    }

    /**
     * Step 1: choose a password. It is only stored after the TOTP code is confirmed.
     */
    public function startPasswordLogin(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)->uncompromised()],
        ]);

        $request->session()->put(self::PENDING, [
            'password_hash' => Hash::make($validated['password']),
            'secret' => $twoFactor->generateSecret(),
        ]);

        return redirect()->route('account.security')->withFragment('passwort');
    }

    /**
     * Step 2: confirm the authenticator app with a code.
     */
    public function confirmPasswordLogin(Request $request, TwoFactor $twoFactor, EnablePasswordLogin $enable): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $pending = $request->session()->get(self::PENDING);

        if (! is_array($pending)) {
            return redirect()->route('account.security');
        }

        $code = preg_replace('/\s+/', '', $validated['code']) ?? '';

        if (! $twoFactor->verifyForSetup($pending['secret'], $code)) {
            return back()->withErrors(['code' => __('Der Code ist ungültig.')])->withFragment('passwort');
        }

        /** @var User $user */
        $user = $request->user();
        $enable($user, $pending['password_hash'], $pending['secret']);
        $request->session()->forget(self::PENDING);

        return redirect()->route('account.security')->with('status', __('Anmeldung mit Passwort und Zwei-Faktor-Code ist aktiv.'));
    }

    public function cancelPasswordLogin(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PENDING);

        return redirect()->route('account.security');
    }

    public function disablePasswordLogin(Request $request, DisablePasswordLogin $disable): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $disable($user);

        return redirect()->route('account.security')->with('status', __('Anmeldung mit Passwort ist deaktiviert.'));
    }
}
