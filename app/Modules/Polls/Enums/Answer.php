<?php

namespace App\Modules\Polls\Enums;

enum Answer: string
{
    case Yes = 'yes';
    case Maybe = 'maybe';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Yes => __('Ja'),
            self::Maybe => __('Wenn nötig'),
            self::No => __('Nein'),
        };
    }
}
