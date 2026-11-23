<?php

use App\Modules\Seo\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Plain files for crawlers: no session, no cookies, own rate limit.
Route::middleware('throttle:seo')->group(function () {
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
    Route::get('/llms.txt', [SeoController::class, 'llms'])->name('seo.llms');
    Route::get('/{key}.txt', [SeoController::class, 'indexNowKey'])
        ->where('key', '[a-f0-9]{32}')
        ->name('seo.indexnow-key');
});
