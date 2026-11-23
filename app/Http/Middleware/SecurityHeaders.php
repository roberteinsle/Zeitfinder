<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy($request));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // no-referrer keeps tokens in URLs (magic, admin and edit links) from leaking.
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), publickey-credentials-get=(self), publickey-credentials-create=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(Request $request): string
    {
        $nonce = Vite::cspNonce();
        $dev = '';

        if (app()->environment('local') && is_file(public_path('hot'))) {
            $hot = trim((string) file_get_contents(public_path('hot')));
            $ws = preg_replace('#^http#', 'ws', $hot);
            $dev = " {$hot} {$ws}";
        }

        // Filament (Livewire/Alpine) needs eval and inline styles; the admin area is
        // additionally protected by passkey step-up and Cloudflare Access.
        if ($request->is('admin', 'admin/*', 'livewire*')) {
            return "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'{$dev}; style-src 'self' 'unsafe-inline'{$dev}; "
                ."img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'{$dev}; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";
        }

        return "default-src 'self'; script-src 'self' 'nonce-{$nonce}'{$dev}; style-src 'self' 'nonce-{$nonce}'{$dev}; "
            ."img-src 'self' data:; font-src 'self'; connect-src 'self'{$dev}; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'";
    }
}
