<?php

namespace App\Modules\Polls\Models;

use App\Support\DisplayTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $poll_id
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $all_day
 * @property-read Poll $poll
 * @property-read Collection<int, Vote> $votes
 */
class PollOption extends Model
{
    protected $fillable = ['starts_at', 'ends_at', 'all_day'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function timeLabel(): string
    {
        if ($this->all_day) {
            return __('Ganztägig');
        }

        $label = DisplayTime::time($this->starts_at);

        return $this->ends_at !== null ? $label.' – '.DisplayTime::time($this->ends_at) : $label;
    }

    public function fullLabel(): string
    {
        return DisplayTime::longDate($this->starts_at).', '.$this->timeLabel();
    }
}
