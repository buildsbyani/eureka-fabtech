<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Clock;
use Illuminate\Http\Request;

/** Reads date, start, duration and group size from the query string, with safe defaults. */
trait ReadsSlotQuery
{
    protected function slotQuery(Request $request): array
    {
        $date = $request->query('date');
        if (! Clock::isValidDate($date)) {
            $date = Clock::today();
        }
        $options = Clock::startOptions($date);
        if (empty($options) && $date === Clock::today()) {
            $date = Clock::now()->addDay()->format('Y-m-d');
            $options = Clock::startOptions($date);
        }
        $start = (int) $request->query('start', $options[0] ?? config('eureka.open'));
        if (! in_array($start, $options, true)) {
            $start = $options[0] ?? config('eureka.open');
        }
        $duration = (int) $request->query('dur', 60);
        if (! in_array($duration, [30, 60, 90, 120, 180], true)) {
            $duration = 60;
        }
        $sizes = array_values(config('eureka.group_sizes'));
        $size = (int) $request->query('size', 0);
        if (! array_key_exists($size, $sizes)) {
            $size = 0;
        }

        return [
            'date' => $date,
            'start' => $start,
            'end' => $start + $duration,
            'duration' => $duration,
            'size' => $size,
            'minSeats' => $sizes[$size],
            'startOptions' => $options,
            'days' => $this->dayStrip($date),
        ];
    }

    /** Five days around the chosen date, plus links to page backwards and forwards. */
    protected function dayStrip(string $date): array
    {
        $today = Clock::today();
        $offset = (int) floor(Clock::now()->startOfDay()->diffInDays(\Carbon\CarbonImmutable::parse($date, config('app.timezone'))) / 5) * 5;
        $first = Clock::now()->addDays($offset);
        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $d = $first->addDays($i);
            $days[] = [
                'date' => $d->format('Y-m-d'),
                'top' => $d->format('Y-m-d') === $today ? 'Today' : $d->format('D'),
                'num' => $d->format('j'),
            ];
        }

        return [
            'list' => $days,
            'label' => $first->format('j M').' to '.$first->addDays(4)->format('j M'),
            'prev' => $offset > 0 ? $first->subDays(5)->format('Y-m-d') : null,
            'next' => $offset + 5 <= config('eureka.book_ahead_days') ? $first->addDays(5)->format('Y-m-d') : null,
        ];
    }
}
