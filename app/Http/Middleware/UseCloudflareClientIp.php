<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses the real visitor IP from Cloudflare for rate limits and logs.
 *
 * Only enabled with BEHIND_CLOUDFLARE=true, which is safe only when the server
 * accepts web traffic exclusively through Cloudflare (tunnel or firewall).
 */
class UseCloudflareClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('zeitfinder.behind_cloudflare')) {
            $ip = $request->headers->get('CF-Connecting-IP');
            if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                $request->server->set('REMOTE_ADDR', $ip);
                $request->headers->remove('X-Forwarded-For');
            }
        }

        return $next($request);
    }
}
