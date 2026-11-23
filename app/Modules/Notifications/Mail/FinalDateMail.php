<?php

namespace App\Modules\Notifications\Mail;

use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Models\PollOption;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FinalDateMail extends Mailable
{
    public function __construct(public Poll $poll, public PollOption $option, public string $pollUrl, public string $ics) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Termin steht: :title', ['title' => $this->poll->title]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.final-date');
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->ics, 'termin.ics')->withMime('text/calendar')];
    }
}
