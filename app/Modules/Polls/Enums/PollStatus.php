<?php

namespace App\Modules\Polls\Enums;

enum PollStatus: string
{
    case Open = 'open';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Offen'),
            self::Finalized => __('Termin steht'),
        };
    }
}
