<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use App\Support\Clock;

/** The two buttons in the check-in reminder email. */
class CheckInLinkController extends Controller
{
    public function keep(Booking $booking)
    {
        if ($booking->status === 'active' && $booking->startsAt()->addMinutes(config('eureka.auto_release_minutes'))->greaterThan(Clock::now())) {
            $booking->update(['checked_in_at' => now()]);

            return view('checkin', ['booking' => $booking->load('room'), 'message' => 'Thanks. The room stays yours.']);
        }

        return view('checkin', ['booking' => $booking->load('room'), 'message' => 'This booking is no longer active, so there is nothing to confirm.']);
    }

    public function release(Booking $booking, BookingService $service)
    {
        if ($booking->status === 'active') {
            $service->release($booking, 'You released it from the reminder email.');
        }

        return view('checkin', ['booking' => $booking->load('room'), 'message' => 'Done. The room is free for others now.']);
    }
}
