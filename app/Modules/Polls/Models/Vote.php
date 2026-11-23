<?php

namespace App\Modules\Polls\Models;

use App\Modules\Polls\Enums\Answer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $participant_id
 * @property int $poll_option_id
 * @property Answer $answer
 */
class Vote extends Model
{
    protected $fillable = ['poll_option_id', 'answer'];

    protected function casts(): array
    {
        return ['answer' => Answer::class];
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<PollOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}
