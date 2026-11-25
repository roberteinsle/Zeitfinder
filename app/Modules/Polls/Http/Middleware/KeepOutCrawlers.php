<?php

namespace App\Modules\Polls\Http\Middleware;

use App\Support\Crawlers;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Poll pages contain names and answers. Known search and AI crawlers get a 403 before the
 * poll is loaded; everything else (including the .ics file) carries X-Robots-Tag.
 */
class KeepOutCrawlers
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = Crawlers::isCrawler($request->userAgent())
            ? response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store, private'])
            : $next($request);

        $response->headers->set('X-Robots-Tag', Crawlers::PRIVATE_PAGE);
        // User-agent specific answers must never end up in a shared cache.
        $response->headers->set('Vary', 'User-Agent', false);

        return $response;
    }
}
