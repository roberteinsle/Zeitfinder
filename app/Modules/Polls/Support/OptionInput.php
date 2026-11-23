<?php

namespace App\Modules\Polls\Support;

use App\Support\DisplayTime;
use Carbon\CarbonImmutable;

/**
 * Turns form rows (date + optional start/end time in Europe/Berlin) into UTC timestamps.
 */
final class OptionInput
{
    /**
     * Validation rules for an options array under $key.
     *
     * @return array<string, mixed>
     */
    public static function rules(string $key, bool $required = true): array
    {
        $max = (int) config('zeitfinder.max_options_per_poll', 60);

        return [
            $key => [$required ? 'required' : 'nullable', 'array', 'max:'.$max],
            "{$key}.*" => ['array'],
            "{$key}.*.date" => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before:2100-01-01'],
            "{$key}.*.start" => ['nullable', 'date_format:H:i'],
            "{$key}.*.end" => ['nullable', 'date_format:H:i', "required_with:{$key}.*.start"],
        ];
    }

    /**
     * @param  array<int, array{date: string, start?: string|null, end?: string|null}>  $rows
     * @return list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable|null, all_day: bool}>
     */
    public static function toOptions(array $rows): array
    {
        $zone = DisplayTime::zone();
        $options = [];
        $seen = [];

        foreach ($rows as $row) {
            $start = $row['start'] ?? null;
            $end = $row['end'] ?? null;

            if ($start === null || $start === '') {
                $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['date'].' 00:00', $zone);
                $option = ['starts_at' => $startsAt->utc(), 'ends_at' => null, 'all_day' => true];
            } else {
                $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['date'].' '.$start, $zone);
                $endsAt = ($end !== null && $end !== '')
                    ? CarbonImmutable::createFromFormat('Y-m-d H:i', $row['date'].' '.$end, $zone)
                    : null;
                // An end before the start means the slot runs past midnight.
                if ($endsAt !== null && $endsAt->lessThanOrEqualTo($startsAt)) {
                    $endsAt = $endsAt->addDay();
                }
                $option = ['starts_at' => $startsAt->utc(), 'ends_at' => $endsAt?->utc(), 'all_day' => false];
            }

            $key = $option['starts_at']->getTimestamp().'|'.($option['ends_at']?->getTimestamp() ?? '').'|'.(int) $option['all_day'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[] = $option;
        }

        usort($options, fn (array $a, array $b) => $a['starts_at'] <=> $b['starts_at']);

        return $options;
    }
}
