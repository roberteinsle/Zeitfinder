<?php

namespace App\Modules\Polls\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $poll_id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string $edit_token_hash
 * @property Carbon|null $created_at
 * @property-read Poll $poll
 * @property-read Collection<int, Vote> $votes
 */
class Participant extends Model
{
    /**
     * poll_id, user_id, email and edit_token_hash are internal (forceFill).
     *
     * @var list<string>
     */
    protected $fillable = ['name'];

    protected $hidden = ['edit_token_hash', 'email'];

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }
}
