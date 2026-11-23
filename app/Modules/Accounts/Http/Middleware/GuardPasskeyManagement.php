<?php

namespace App\Modules\Accounts\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins who already have a passkey may only add or remove passkeys in a session
 * that was verified with a passkey. Otherwise a stolen magic-link session could
 * register its own passkey and reach the admin area.
 */
class GuardPasskeyManagement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->is_admin && $user->hasPasskeysEnabled()
            && ! $request->session()->has(EnsurePasskeySession::SESSION_KEY)) {
            abort(403, __('Bitte bestätige zuerst mit einem vorhandenen Passkey.'));
        }

        return $next($request);
    }
}
