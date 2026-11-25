<?php

namespace App\Modules\Notifications;

use App\Models\ActivityLog;
use App\Modules\Notifications\Mail\FinalDateMail;
use App\Modules\Notifications\Mail\NewVoteMail;
use App\Modules\Notifications\Mail\PollCreatedMail;
use App\Modules\Notifications\Mail\VoteReceiptMail;
use App\Modules\Notifications\Support\SafeMailer;
use App\Modules\Polls\Events\DuplicateVoteAttempted;
use App\Modules\Polls\Events\ParticipantVoted;
use App\Modules\Polls\Events\PollCreated;
use App\Modules\Polls\Events\PollFinalized;
use App\Support\IcsCalendar;
use App\Support\Registries\AdminStat;
use App\Support\Registries\AdminStatRegistry;
use App\Support\Registries\DomainEventRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Email notifications. Reacts to poll events registered in the DomainEventRegistry;
 * the polls module knows nothing about mails.
 */
class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(DomainEventRegistry $events, AdminStatRegistry $stats): void
    {
        $stats->register(new AdminStat('notifications.failed', __('Fehlgeschlagene Mails'),
            fn () => (int) ActivityLog::query()->where('action', 'mail.failed')->where('created_at', '>=', now()->subDays(30))
                ->get()->sum(fn (ActivityLog $log) => (int) ($log->properties['failed'] ?? 0)),
            fn () => __('Benachrichtigungen, letzte 30 Tage'), warnAboveZero: true, order: 60));

        $events->listen(PollCreated::class, function (PollCreated $event) {
            $poll = $event->poll;
            $this->mailer()->sendLater([
                $poll->organizer_email => new PollCreatedMail(
                    $poll,
                    route('polls.show', $poll),
                    route('polls.manage.grant', [$poll, $event->adminToken]),
                ),
            ], 'poll.created', $poll->id);
        });

        $events->listen(ParticipantVoted::class, function (ParticipantVoted $event) {
            $poll = $event->poll;
            $mails = [];

            if ($event->isNew && $event->editToken !== null) {
                $mails[$event->participant->email] = new VoteReceiptMail(
                    $poll,
                    $event->participant->name,
                    route('polls.vote.claim', [$poll, $event->editToken]),
                );
            }

            if ($poll->notify_on_vote && $poll->organizer_email !== $event->participant->email) {
                $mails[$poll->organizer_email] = new NewVoteMail($poll, $event->participant->name, ! $event->isNew, route('polls.manage', $poll));
            }

            $this->mailer()->sendLater($mails, 'participant.voted', $poll->id);
        });

        $events->listen(DuplicateVoteAttempted::class, function (DuplicateVoteAttempted $event) {
            $this->mailer()->sendLater([
                $event->participant->email => new VoteReceiptMail(
                    $event->poll,
                    $event->participant->name,
                    route('polls.vote.claim', [$event->poll, $event->editToken]),
                    duplicate: true,
                ),
            ], 'participant.duplicate', $event->poll->id);
        });

        $events->listen(PollFinalized::class, function (PollFinalized $event) {
            $poll = $event->poll;
            $option = $event->option;
            $pollUrl = route('polls.show', $poll);
            $ics = IcsCalendar::event(
                uid: 'poll-'.$poll->public_id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
                summary: $poll->title,
                start: $option->starts_at,
                end: $option->ends_at,
                allDay: $option->all_day,
                description: $poll->description,
                location: $poll->location,
                url: $pollUrl,
            );

            $recipients = $poll->participants()->pluck('email')->push($poll->organizer_email)->unique();
            $mails = [];
            foreach ($recipients as $email) {
                $mails[$email] = new FinalDateMail($poll, $option, $pollUrl, $ics);
            }

            $this->mailer()->sendLater($mails, 'poll.finalized', $poll->id);
        });
    }

    private function mailer(): SafeMailer
    {
        return $this->app->make(SafeMailer::class);
    }
}
