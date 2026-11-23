<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formats UTC timestamps for display in the configured time zone (Europe/Berlin).
 */
final class DisplayTime
{
    public static function zone(): string
    {
        return (string) config('zeitfinder.display_timezone', 'Europe/Berlin');
    }

    public static function local(CarbonInterface $time): CarbonInterface
    {
        return $time->copy()->setTimezone(self::zone())->locale('de');
    }

    public static function weekday(CarbonInterface $time): string
    {
        return self::local($time)->isoFormat('dd');
    }

    public static function date(CarbonInterface $time): string
    {
        return self::local($time)->isoFormat('D. MMM');
    }

    public static function longDate(CarbonInterface $time): string
    {
        return self::local($time)->isoFormat('dddd, D. MMMM YYYY');
    }

    public static function time(CarbonInterface $time): string
    {
        return self::local($time)->format('H:i');
    }

    public static function dateTime(CarbonInterface $time): string
    {
        return self::local($time)->isoFormat('D. MMM YYYY, HH:mm');
    }
}
