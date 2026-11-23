<?php

use App\Modules\Accounts\Actions\RequestMagicLink;
use App\Modules\Notifications\Mail\PollCreatedMail;
use App\Modules\Notifications\Support\SafeMailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

it('logs the SMTP error of a failed magic link mail without the token', function () {
    $token = str_repeat('ab', 32);
    Mail::shouldReceive('to->send')->andThrow(new TransportException('Expected response code "250" but got "535 Authentication failed" '.$token));
    Log::spy();

    app(RequestMagicLink::class)('erika@example.com');

    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => $message === 'Magic link mail could not be sent.'
        && $context['exception'] === TransportException::class
        && str_contains($context['message'], '535 Authentication failed')
        && ! str_contains($context['message'], $token))->once();
});

it('logs the SMTP error of a failed notification mail and keeps going', function () {
    [$poll] = createPoll();
    Mail::shouldReceive('to->send')->andThrow(new TransportException('Connection could not be established with host "smtp.example.com:587"'));
    Log::spy();

    app(SafeMailer::class)->sendLater(['erika@example.com' => new PollCreatedMail($poll, 'https://zeitfinder.example.com/u', 'https://zeitfinder.example.com/v')], 'poll.created', $poll->id);

    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => $message === 'Notification mail failed.'
        && str_contains($context['message'], 'smtp.example.com:587'))->once();
});

it('logs every sent magic link with masked recipient and message id', function () {
    Log::spy();

    app(RequestMagicLink::class)('erika@example.com');

    Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context) => $message === 'Mail sent.'
        && $context['mail'] === 'magic_link'
        && $context['recipient'] === 'e***@example.com'
        && is_string($context['message_id']))->once();
});

it('refuses the log mailer in production so tokens never end up in the log', function () {
    $this->app->detectEnvironment(fn () => 'production');
    config(['mail.default' => 'log']);
    Mail::fake();
    Log::spy();

    app(RequestMagicLink::class)('erika@example.com');

    Mail::assertNothingSent();
    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => str_starts_with($message, 'Mail is not configured')
        && $context['mailer'] === 'log')->once();
});
