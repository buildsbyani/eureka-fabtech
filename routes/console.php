<?php

use Illuminate\Support\Facades\Schedule;

// Sends check-in reminders and releases rooms nobody confirmed.
// Needs the Laravel scheduler running: `* * * * * php /path/to/artisan schedule:run`
Schedule::command('eureka:check-ins')->everyMinute()->withoutOverlapping();
