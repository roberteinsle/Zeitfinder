<?php

namespace App\Modules\Polls\Http\Controllers;

use App\Models\User;
use App\Modules\Polls\Actions\SubmitVote;
use App\Modules\Polls\Enums\Answer;
use App\Modules\Polls\Exceptions\DuplicateParticipantException;
use App\Modules\Polls\Exceptions\PollClosedException;
use App\Modules\Polls\Exceptions\PollFullException;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Support\PollAccess;
use App\Support\EmailAddress;
use App\Support\Tokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoteController
{
    public function store(Request $request, Poll $poll, SubmitVote $submit, PollAccess $access): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($access->participantFor($request, $poll) !== null) {
            return $this->update($request, $poll, $submit, $access);
        }

        if ($user !== null) {
            $request->merge(['email' => $user->email]);
        } else {
            $request->merge(['email' => EmailAddress::normalize($request->input('email'))]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => EmailAddress::rules(),
            ...$this->answerRules(),
        ]);

        try {
            [$participant] = $submit->create($poll, trim($validated['name']), $validated['email'], $validated['answers'] ?? [], $user);
        } catch (DuplicateParticipantException) {
            return back()->withInput()->withErrors([
                'email' => __('Mit dieser E-Mail-Adresse wurde schon abgestimmt. Wir haben einen Link zum Ändern der Antwort an diese Adresse geschickt.'),
            ]);
        } catch (PollClosedException) {
            return back()->withErrors(['poll' => __('Die Abstimmung ist beendet.')]);
        } catch (PollFullException) {
            return back()->withErrors(['poll' => __('Diese Umfrage hat die maximale Zahl an Teilnehmenden erreicht.')]);
        }

        $access->rememberParticipant($request, $poll, $participant);

        return redirect()->route('polls.show', $poll)->withFragment('ergebnis')
            ->with('status', __('Danke! Deine Antwort ist gespeichert. Einen Link zum Ändern haben wir dir per E-Mail geschickt.'));
    }

    public function update(Request $request, Poll $poll, SubmitVote $submit, PollAccess $access): RedirectResponse
    {
        $participant = $access->participantFor($request, $poll);
        abort_if($participant === null, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            ...$this->answerRules(),
        ]);

        try {
            $submit->update($poll, $participant, trim($validated['name']), $validated['answers'] ?? []);
        } catch (PollClosedException) {
            return back()->withErrors(['poll' => __('Die Abstimmung ist beendet.')]);
        }

        return redirect()->route('polls.show', $poll)->withFragment('ergebnis')
            ->with('status', __('Deine Antwort wurde aktualisiert.'));
    }

    /**
     * Opens the personal edit link from the email and remembers the participant in this browser.
     */
    public function claim(Request $request, Poll $poll, string $token, PollAccess $access): RedirectResponse
    {
        $participant = $poll->participants()->where('edit_token_hash', Tokens::hash($token))->first();
        abort_if($participant === null, 404);

        $access->rememberParticipant($request, $poll, $participant);

        return redirect()->route('polls.show', $poll)->with('status', __('Du kannst deine Antwort jetzt ändern.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function answerRules(): array
    {
        return [
            'answers' => ['nullable', 'array', 'max:'.(int) config('zeitfinder.max_options_per_poll', 60)],
            'answers.*' => ['required', 'string', Rule::enum(Answer::class)],
        ];
    }
}
