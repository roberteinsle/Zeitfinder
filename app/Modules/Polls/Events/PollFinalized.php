<?php

namespace App\Modules\Polls\Events;

use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Models\PollOption;

final readonly class PollFinalized
{
    public function __construct(public Poll $poll, public PollOption $option) {}
}
