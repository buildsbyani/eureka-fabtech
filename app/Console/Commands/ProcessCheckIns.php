<?php

namespace App\Console\Commands;

use App\Mail\NoticeMail;
use App\Models\Booking;
use App\Services\BookingService;
use App\Support\Clock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ProcessCheckIns extends Command
{
    protected $signature = 'eureka:check-ins';

    protected $description = 'Email check-in reminders before meetings and release rooms nobody confirmed.';

    public function handle(BookingService $service): int
    {
        $today = Clock::today();
        $now = Clock::nowMinutes();
        $remind = config('eureka.checkin_reminder_minutes');
        $grace = config('eureka.auto_release_minutes');

        // 1. Reminders: meetings starting within the next few minutes that were not reminded yet.
        $due = Booking::active()->onDate($today)->with('room', 'organizer')
            ->whereNull('checked_in_at')->whereNull('reminder_sent_at')
            ->where('start_min', '<=', $now + $remind)->where('start_min', '>', $now - $grace)
            ->get();
        foreach ($due as $b) {
            $expires = $b->startsAt()->addMinutes($grace + 5);
            Mail::to($b->organizer->email)->send(new NoticeMail(
                'Still need '.$b->room->name.' at '.Clock::time($b->start_min).'?',
                'reminder',
                [
                    'booking' => $b,
                    'keepUrl' => URL::temporarySignedRoute('checkin.keep', $expires, ['booking' => $b->id]),
                    'releaseUrl' => URL::temporarySignedRoute('checkin.release', $expires, ['booking' => $b->id]),
                    'deadline' => Clock::time($b->start_min + $grace),
                ],
            ));
            $b->update(['reminder_sent_at' => now()]);
        }

        // 2. Release: nobody confirmed within the grace period after the start.
        $stale = Booking::active()->onDate($today)->whereNull('checked_in_at')
            ->where('start_min', '<=', $now - $grace)->where('end_min', '>', $now)->get();
        foreach ($stale as $b) {
            $service->release($b, 'Nobody confirmed by '.Clock::time($b->start_min + $grace).'.');
        }

        $this->info("Reminders: {$due->count()}, released: {$stale->count()}");

        return self::SUCCESS;
    }
}
