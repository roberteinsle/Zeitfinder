<?php

namespace App\Modules\Accounts\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only lets the request through if the current session was verified with a passkey.
 */
class EnsurePasskeySession
{
    public const SESSION_KEY = 'auth.passkey_verified_at';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('passkey.step-up', ['next' => '/'.ltrim($request->path(), '/')]);
        }

        return $next($request);
    }
}
