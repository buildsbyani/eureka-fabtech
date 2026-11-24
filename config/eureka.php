<?php

$toMinutes = function (string $hhmm): int {
    [$h, $m] = array_map('intval', explode(':', $hhmm));

    return $h * 60 + $m;
};

return [
    // Rooms can be booked between these times, in 30-minute steps.
    'open' => $toMinutes(env('EUREKA_OPEN', '08:00')),
    'close' => $toMinutes(env('EUREKA_CLOSE', '20:00')),
    'slot' => 30,

    // Check-in: a reminder goes to the organiser this many minutes before the start,
    // and the room is released if nobody confirms within this many minutes after the start.
    'checkin_reminder_minutes' => (int) env('EUREKA_CHECKIN_REMINDER_MINUTES', 10),
    'auto_release_minutes' => (int) env('EUREKA_AUTO_RELEASE_MINUTES', 10),

    // Recurring bookings repeat weekly for at most this many weeks (3 months).
    'max_repeat_weeks' => (int) env('EUREKA_MAX_REPEAT_WEEKS', 12),

    // How far ahead people can book.
    'book_ahead_days' => (int) env('EUREKA_BOOK_AHEAD_DAYS', 60),

    // Group size options shown as chips: label => minimum seats needed.
    'group_sizes' => [
        'Just me' => 1,
        '2 to 4' => 4,
        '5 to 8' => 8,
        '9 or more' => 9,
    ],
];
