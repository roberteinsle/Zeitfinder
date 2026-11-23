<?php

namespace App\Modules\Polls\Support;

use App\Models\User;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Who may manage a poll and which participant the visitor is.
 *
 * Grants for visitors without an account live in encrypted, HTTP-only cookies
 * (set after opening the secret admin or edit link). Signed-in users are recognised
 * by account.
 */
final class PollAccess
{
    private const MANAGE_COOKIE = 'zf_manage';

    private const VOTE_COOKIE = 'zf_votes';

    private const MAX_ENTRIES = 50;

    private const MINUTES = 60 * 24 * 365;

    public function canManage(Request $request, Poll $poll): bool
    {
        $user = $request->user();
        if ($user instanceof User && $poll->user_id !== null && $poll->user_id === $user->id) {
            return true;
        }

        return in_array($poll->id, $this->manageGrants($request), true);
    }

    public function grantManage(Request $request, Poll $poll): void
    {
        $grants = array_values(array_diff($this->manageGrants($request), [$poll->id]));
        $grants[] = $poll->id;
        $grants = array_slice($grants, -self::MAX_ENTRIES);

        Cookie::queue($this->makeCookie(self::MANAGE_COOKIE, $grants));
    }

    public function participantFor(Request $request, Poll $poll): ?Participant
    {
        $user = $request->user();
        if ($user instanceof User) {
            $participant = $poll->participants()
                ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', mb_strtolower($user->email)))
                ->first();
            if ($participant !== null) {
                return $participant;
            }
        }

        $id = $this->voteGrants($request)[$poll->id] ?? null;

        return $id !== null ? $poll->participants()->whereKey($id)->first() : null;
    }

    public function rememberParticipant(Request $request, Poll $poll, Participant $participant): void
    {
        $grants = $this->voteGrants($request);
        unset($grants[$poll->id]);
        $grants[$poll->id] = $participant->id;
        $grants = array_slice($grants, -self::MAX_ENTRIES, null, true);

        Cookie::queue($this->makeCookie(self::VOTE_COOKIE, $grants));
    }

    /**
     * @return list<int>
     */
    private function manageGrants(Request $request): array
    {
        $decoded = json_decode((string) $request->cookie(self::MANAGE_COOKIE, '[]'), true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_int')) : [];
    }

    /**
     * @return array<int, int> poll id => participant id
     */
    private function voteGrants(Request $request): array
    {
        $decoded = json_decode((string) $request->cookie(self::VOTE_COOKIE, '{}'), true);
        if (! is_array($decoded)) {
            return [];
        }

        $grants = [];
        foreach ($decoded as $pollId => $participantId) {
            if (is_int($participantId) && is_numeric($pollId)) {
                $grants[(int) $pollId] = $participantId;
            }
        }

        return $grants;
    }

    /**
     * @param  array<int, int>  $value
     */
    private function makeCookie(string $name, array $value): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make($name, (string) json_encode($value), self::MINUTES, '/', null, null, true, false, 'lax');
    }
}
