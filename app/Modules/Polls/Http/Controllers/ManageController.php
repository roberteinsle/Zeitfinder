<?php

namespace App\Modules\Polls\Http\Controllers;

use App\Modules\Polls\Actions\DeletePoll;
use App\Modules\Polls\Actions\FinalizePoll;
use App\Modules\Polls\Actions\RemoveParticipant;
use App\Modules\Polls\Actions\ReopenPoll;
use App\Modules\Polls\Actions\UpdatePoll;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Models\PollOption;
use App\Modules\Polls\Support\OptionInput;
use App\Modules\Polls\Support\PollAccess;
use App\Modules\Polls\Support\PollResults;
use App\Support\Tokens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageController
{
    public function __construct(private readonly PollAccess $access) {}

    /**
     * Secret admin link from the email: grants management rights in this browser.
     */
    public function grant(Request $request, Poll $poll, string $token): RedirectResponse
    {
        abort_unless(hash_equals($poll->admin_token_hash, Tokens::hash($token)), 404);

        $this->access->grantManage($request, $poll);

        return redirect()->route('polls.manage', $poll);
    }

    public function show(Request $request, Poll $poll): View
    {
        $this->authorize($request, $poll);
        $poll->load(['options', 'participants.votes', 'finalOption']);

        return view('polls.manage', ['poll' => $poll, 'results' => new PollResults($poll)]);
    }

    public function update(Request $request, Poll $poll, UpdatePoll $update): RedirectResponse
    {
        $this->authorize($request, $poll);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:200'],
            'allow_maybe' => ['boolean'],
            'notify_on_vote' => ['boolean'],
            'remove_options' => ['nullable', 'array'],
            'remove_options.*' => ['integer'],
            ...OptionInput::rules('new_options', required: false),
        ]);
        $validated['allow_maybe'] = $request->boolean('allow_maybe');
        $validated['notify_on_vote'] = $request->boolean('notify_on_vote');

        $update($poll, $validated);

        return redirect()->route('polls.manage', $poll)->with('status', __('Änderungen gespeichert.'));
    }

    public function finalize(Request $request, Poll $poll, FinalizePoll $finalize): RedirectResponse
    {
        $this->authorize($request, $poll);

        $validated = $request->validate(['option_id' => ['required', 'integer']]);
        /** @var PollOption $option */
        $option = $poll->options()->whereKey($validated['option_id'])->firstOrFail();

        $finalize($poll, $option);

        return redirect()->route('polls.manage', $poll)
            ->with('status', __('Der Termin steht fest. Alle Teilnehmenden werden per E-Mail benachrichtigt.'));
    }

    public function reopen(Request $request, Poll $poll, ReopenPoll $reopen): RedirectResponse
    {
        $this->authorize($request, $poll);
        $reopen($poll);

        return redirect()->route('polls.manage', $poll)->with('status', __('Die Abstimmung ist wieder offen.'));
    }

    public function removeParticipant(Request $request, Poll $poll, Participant $participant, RemoveParticipant $remove): RedirectResponse
    {
        $this->authorize($request, $poll);
        $remove($poll, $participant);

        return redirect()->route('polls.manage', $poll)->with('status', __('Teilnahme entfernt.'));
    }

    public function destroy(Request $request, Poll $poll, DeletePoll $delete): RedirectResponse
    {
        $this->authorize($request, $poll);
        $delete($poll);

        return redirect()->route('home')->with('status', __('Die Umfrage wurde gelöscht.'));
    }

    private function authorize(Request $request, Poll $poll): void
    {
        abort_unless($this->access->canManage($request, $poll), 403);
    }
}
