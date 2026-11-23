<?php

namespace App\Modules\Notifications\Support;

use App\Support\ActivityLogger;
use App\Support\MailDelivery;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends mails after the response. A failed mail is logged and reported in the
 * activity log, but never undoes the action that triggered it.
 */
final class SafeMailer
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly MailDelivery $delivery,
    ) {}

    /**
     * @param  array<string, Mailable>  $mails  recipient => mailable
     */
    public function sendLater(array $mails, string $context, ?int $pollId = null): void
    {
        if ($mails === []) {
            return;
        }

        defer(function () use ($mails, $context, $pollId) {
            if (! $this->delivery->canDeliver($context)) {
                $this->activity->log('mail.failed', null, ['context' => $context, 'poll_id' => $pollId, 'failed' => count($mails), 'total' => count($mails)]);

                return;
            }

            $failed = 0;
            foreach ($mails as $recipient => $mail) {
                try {
                    $this->delivery->sent($context, $recipient, Mail::to($recipient)->send($mail));
                } catch (Throwable $e) {
                    $failed++;
                    $this->delivery->failed('Notification mail failed.', $e, ['context' => $context, 'poll_id' => $pollId]);
                }
            }

            if ($failed > 0) {
                $this->activity->log('mail.failed', null, ['context' => $context, 'poll_id' => $pollId, 'failed' => $failed, 'total' => count($mails)]);
            }
        });
    }
}
