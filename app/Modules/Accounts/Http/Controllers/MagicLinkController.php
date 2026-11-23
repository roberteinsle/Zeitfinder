<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Actions\CompleteLogin;
use App\Modules\Accounts\Actions\RedeemMagicLink;
use App\Modules\Accounts\Actions\RequestMagicLink;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MagicLinkController
{
    public function store(Request $request, RequestMagicLink $requestMagicLink): RedirectResponse
    {
        $request->merge(['email' => EmailAddress::normalize($request->input('email'))]);
        $validated = $request->validate(['email' => EmailAddress::rules()]);
        $email = $validated['email'];

        // Same answer and timing for known and unknown addresses: the work runs after the response.
        defer(fn () => app(RequestMagicLink::class)($email));

        return redirect()->route('login')
            ->with('status', __('Falls die Adresse gültig ist, haben wir dir einen Anmeldelink geschickt. Er ist 15 Minuten gültig.'));
    }

    /**
     * Mail scanners open links in advance, so GET only shows a confirmation button.
     */
    public function show(string $token): View
    {
        return view('accounts.magic-link', ['token' => $token]);
    }

    public function redeem(Request $request, string $token, RedeemMagicLink $redeem, CompleteLogin $login): RedirectResponse
    {
        $user = $redeem($token);

        if ($user === null) {
            return redirect()->route('login')
                ->withErrors(['email' => __('Der Link ist ungültig, abgelaufen oder wurde schon benutzt. Fordere einfach einen neuen an.')]);
        }

        if ($user->isBlocked()) {
            return redirect()->route('login')->withErrors(['email' => __('Dieses Konto ist gesperrt.')]);
        }

        $login($request, $user, 'magic_link', remember: true);

        $redirect = redirect()->intended(route('account'));

        // Suggest a passkey after the first sign-in via magic link.
        if (! $user->hasPasskeysEnabled()) {
            $redirect->with('suggest_passkey', true);
        }

        return $redirect;
    }
}
