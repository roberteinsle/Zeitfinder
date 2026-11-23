<?php

namespace App\Modules\Polls\Http\Controllers;

use App\Models\User;
use App\Modules\Polls\Actions\CreatePoll;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Support\OptionInput;
use App\Modules\Polls\Support\PollAccess;
use App\Modules\Polls\Support\PollResults;
use App\Support\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PollController
{
    public function create(): View
    {
        return view('polls.create');
    }

    public function store(Request $request, CreatePoll $createPoll, PollAccess $access): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $request->merge(['organizer_email' => $user->email, 'organizer_name' => $request->input('organizer_name') ?: $user->name]);
        } else {
            $request->merge(['organizer_email' => EmailAddress::normalize($request->input('organizer_email'))]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:200'],
            'organizer_name' => ['required', 'string', 'max:80'],
            'organizer_email' => EmailAddress::rules(),
            'allow_maybe' => ['boolean'],
            'notify_on_vote' => ['boolean'],
            ...OptionInput::rules('options'),
        ]);

        $validated['allow_maybe'] = $request->boolean('allow_maybe');
        $validated['notify_on_vote'] = $request->boolean('notify_on_vote');

        [$poll, $adminToken] = $createPoll($validated, $user);
        $access->grantManage($request, $poll);

        return redirect()->route('polls.created', $poll)->with('admin_url', route('polls.manage.grant', [$poll, $adminToken]));
    }

    public function created(Request $request, Poll $poll, PollAccess $access): View
    {
        abort_unless($access->canManage($request, $poll), 403);

        return view('polls.created', ['poll' => $poll, 'adminUrl' => session('admin_url')]);
    }

    public function show(Request $request, Poll $poll, PollAccess $access): View
    {
        $poll->load(['options', 'participants.votes', 'finalOption']);
        $participant = $access->participantFor($request, $poll);

        return view('polls.show', [
            'poll' => $poll,
            'results' => new PollResults($poll),
            'me' => $participant,
            'canManage' => $access->canManage($request, $poll),
        ]);
    }
}
