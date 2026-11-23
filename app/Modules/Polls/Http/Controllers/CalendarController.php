<?php

namespace App\Modules\Polls\Http\Controllers;

use App\Modules\Polls\Models\Poll;
use App\Support\IcsCalendar;
use Illuminate\Http\Response;

class CalendarController
{
    public function __invoke(Poll $poll): Response
    {
        $option = $poll->finalOption;
        abort_if(! $poll->isFinalized() || $option === null, 404);

        $ics = IcsCalendar::event(
            uid: 'poll-'.$poll->public_id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            summary: $poll->title,
            start: $option->starts_at,
            end: $option->ends_at,
            allDay: $option->all_day,
            description: $poll->description,
            location: $poll->location,
            url: route('polls.show', $poll),
        );

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="termin.ics"',
        ]);
    }
}
