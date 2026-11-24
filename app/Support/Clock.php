<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Time helpers. Bookings store a date plus minutes after midnight in the office time zone
 * (APP_TIMEZONE, Asia/Kolkata by default).
 */
class Clock
{
    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'));
    }

    public static function today(): string
    {
        return self::now()->format('Y-m-d');
    }

    public static function nowMinutes(): int
    {
        $now = self::now();

        return $now->hour * 60 + $now->minute;
    }

    public static function at(string $date, int $minutes): CarbonImmutable
    {
        return CarbonImmutable::parse($date, config('app.timezone'))->startOfDay()->addMinutes($minutes);
    }

    public static function time(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        $suffix = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h % 12 ?: 12;

        return $h12.':'.str_pad((string) $m, 2, '0', STR_PAD_LEFT).' '.$suffix;
    }

    public static function range(int $start, int $end): string
    {
        return self::time($start).' – '.self::time($end);
    }

    public static function duration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }
        $h = $minutes / 60;

        return (fmod($h, 1.0) === 0.0 ? (int) $h : $h).' hr';
    }

    public static function dayLabel(string $date): string
    {
        $today = self::today();
        if ($date === $today) {
            return 'Today';
        }
        if ($date === self::now()->addDay()->format('Y-m-d')) {
            return 'Tomorrow';
        }

        return CarbonImmutable::parse($date)->format('D j M');
    }

    public static function longDate(string $date): string
    {
        return CarbonImmutable::parse($date)->format('l, j F');
    }

    /** Start times that can still be booked on a date, in 30-minute steps. */
    public static function startOptions(string $date): array
    {
        $open = config('eureka.open');
        $close = config('eureka.close');
        $slot = config('eureka.slot');
        $from = $open;
        if ($date === self::today()) {
            $from = max($open, (int) (ceil(self::nowMinutes() / $slot) * $slot));
        }
        $options = [];
        for ($m = $from; $m < $close; $m += $slot) {
            $options[] = $m;
        }

        return $options;
    }

    public static function isValidDate(?string $date): bool
    {
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $last = self::now()->addDays(config('eureka.book_ahead_days'))->format('Y-m-d');

        return $date >= self::today() && $date <= $last;
    }
}
