<?php

use App\Modules\Polls\Http\Controllers\CalendarController;
use App\Modules\Polls\Http\Controllers\ManageController;
use App\Modules\Polls\Http\Controllers\PollController;
use App\Modules\Polls\Http\Controllers\VoteController;
use App\Modules\Polls\Http\Middleware\KeepOutCrawlers;
use App\Support\Tokens;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::pattern('poll', '[A-Za-z0-9]{12}');
    Route::pattern('token', Tokens::PATTERN);

    Route::get('/neu', [PollController::class, 'create'])->name('polls.create');
    Route::post('/neu', [PollController::class, 'store'])->middleware('throttle:poll-create')->name('polls.store');
});

// Everything below /u/ is private to the people who have the link: no crawlers, never indexed.
// Outermost middleware, so it runs before session and database and also marks error responses.
Route::middleware([KeepOutCrawlers::class, 'web'])->group(function () {
    Route::get('/u/{poll}', [PollController::class, 'show'])->name('polls.show');
    Route::get('/u/{poll}/bereit', [PollController::class, 'created'])->name('polls.created');
    Route::get('/u/{poll}/termin.ics', CalendarController::class)->name('polls.calendar');

    Route::middleware('throttle:vote')->group(function () {
        Route::post('/u/{poll}/antwort', [VoteController::class, 'store'])->name('polls.vote');
        Route::put('/u/{poll}/antwort', [VoteController::class, 'update'])->name('polls.vote.update');
    });

    Route::middleware('throttle:secret-links')->group(function () {
        Route::get('/u/{poll}/antwort/{token}', [VoteController::class, 'claim'])->name('polls.vote.claim');
        Route::get('/u/{poll}/verwalten/{token}', [ManageController::class, 'grant'])->name('polls.manage.grant');
    });

    Route::middleware('throttle:poll-manage')->group(function () {
        Route::get('/u/{poll}/verwalten', [ManageController::class, 'show'])->name('polls.manage');
        Route::put('/u/{poll}/verwalten', [ManageController::class, 'update'])->name('polls.manage.update');
        Route::post('/u/{poll}/festlegen', [ManageController::class, 'finalize'])->name('polls.finalize');
        Route::post('/u/{poll}/wieder-oeffnen', [ManageController::class, 'reopen'])->name('polls.reopen');
        Route::delete('/u/{poll}/teilnehmende/{participant}', [ManageController::class, 'removeParticipant'])
            ->whereNumber('participant')->name('polls.participants.destroy');
        Route::delete('/u/{poll}', [ManageController::class, 'destroy'])->name('polls.destroy');
    });
});
