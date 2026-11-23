<?php

use App\Modules\Polls\Actions\SubmitVote;
use App\Modules\Polls\Support\PollResults;

it('counts answers and finds the best option', function () {
    [$poll] = createPoll();
    [$a, $b, $c] = $poll->options->all();
    $submit = app(SubmitVote::class);
    $submit->create($poll, 'Max', 'max@example.com', [$a->id => 'yes', $b->id => 'yes', $c->id => 'no']);
    $submit->create($poll, 'Lisa', 'lisa@example.com', [$a->id => 'yes', $b->id => 'maybe', $c->id => 'yes']);
    $submit->create($poll, 'Tom', 'tom@example.com', [$a->id => 'no', $b->id => 'yes', $c->id => 'maybe']);

    $results = new PollResults($poll->fresh());

    expect($results->counts[$a->id])->toBe(['yes' => 2, 'maybe' => 0, 'no' => 1])
        ->and($results->counts[$b->id])->toBe(['yes' => 2, 'maybe' => 1, 'no' => 0])
        // Same number of "yes": more "maybe" wins.
        ->and($results->bestOptionIds)->toBe([$b->id])
        ->and($results->ranked()[0]->id)->toBe($b->id)
        ->and($results->availability($b->id))->toBe(100);
});

it('reports ties as several best options', function () {
    [$poll] = createPoll();
    [$a, $b] = $poll->options->all();
    app(SubmitVote::class)->create($poll, 'Max', 'max@example.com', [$a->id => 'yes', $b->id => 'yes']);

    expect((new PollResults($poll->fresh()))->bestOptionIds)->toBe([$a->id, $b->id]);
});

it('has no best option without positive answers', function () {
    [$poll] = createPoll();
    app(SubmitVote::class)->create($poll, 'Max', 'max@example.com', []);

    expect((new PollResults($poll->fresh()))->bestOptionIds)->toBe([]);
});
