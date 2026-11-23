<?php

namespace App\Modules\Notifications\Mail;

use App\Modules\Polls\Models\Poll;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewVoteMail extends Mailable
{
    public function __construct(public Poll $poll, public string $participantName, public bool $updated, public string $manageUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':name hat bei „:title“ abgestimmt', ['name' => $this->participantName, 'title' => $this->poll->title]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-vote');
    }
}
