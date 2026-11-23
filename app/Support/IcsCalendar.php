<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Builds a minimal iCalendar (RFC 5545) file for a single event.
 */
final class IcsCalendar
{
    public static function event(
        string $uid,
        string $summary,
        CarbonInterface $start,
        ?CarbonInterface $end,
        bool $allDay,
        ?string $description = null,
        ?string $location = null,
        ?string $url = null,
    ): string {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Zeitfinder//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.self::escape($uid),
            'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z'),
        ];

        if ($allDay) {
            $localStart = DisplayTime::local($start);
            $lines[] = 'DTSTART;VALUE=DATE:'.$localStart->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$localStart->copy()->addDay()->format('Ymd');
        } else {
            $lines[] = 'DTSTART:'.$start->copy()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.($end ?? $start->copy()->addHour())->copy()->utc()->format('Ymd\THis\Z');
        }

        $lines[] = 'SUMMARY:'.self::escape($summary);

        if ($description !== null && $description !== '') {
            $lines[] = 'DESCRIPTION:'.self::escape($description);
        }
        if ($location !== null && $location !== '') {
            $lines[] = 'LOCATION:'.self::escape($location);
        }
        if ($url !== null) {
            $lines[] = 'URL:'.self::escape($url);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    private static function escape(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\\,', '\\n'], $value);
    }

    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = [];
        while (strlen($line) > 75) {
            $chunk = mb_strcut($line, 0, 75, 'UTF-8');
            $out[] = $chunk;
            $line = ' '.substr($line, strlen($chunk));
        }
        $out[] = $line;

        return implode("\r\n", $out);
    }
}
