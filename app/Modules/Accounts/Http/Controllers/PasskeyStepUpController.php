<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Models\User;
use App\Support\SafeRedirect;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The admin area requires a passkey verification in the current session.
 */
class PasskeyStepUpController
{
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        redirect()->setIntendedUrl(SafeRedirect::internalPath($request->query('next'), '/admin'));

        return view('accounts.passkey-step-up', ['hasPasskeys' => $user->hasPasskeysEnabled()]);
    }
}
