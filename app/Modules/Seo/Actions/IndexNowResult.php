<?php

namespace App\Modules\Seo\Actions;

enum IndexNowResult: string
{
    case Submitted = 'submitted';
    case Failed = 'failed';
    case Throttled = 'throttled';
    case Disabled = 'disabled';
    case NotPublic = 'not_public';

    public function message(): string
    {
        return match ($this) {
            self::Submitted => __('Die Seiten wurden an IndexNow gemeldet.'),
            self::Failed => __('IndexNow hat die Meldung abgelehnt oder war nicht erreichbar. Details stehen im Log.'),
            self::Throttled => __('Bitte warte ein paar Minuten bis zur nächsten Meldung.'),
            self::Disabled => __('IndexNow oder die Indexierung ist ausgeschaltet.'),
            self::NotPublic => __('IndexNow braucht eine öffentliche https-Adresse (APP_URL).'),
        };
    }
}
