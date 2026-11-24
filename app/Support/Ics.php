<?php

namespace App\Support;

use App\Models\Booking;

/** Builds a calendar invite (iCalendar) that Outlook, Gmail and phone calendars accept. */
class Ics
{
    public static function forBooking(Booking $booking, string $method = 'REQUEST'): string
    {
        $booking->loadMissing('room', 'organizer');
        $fmt = fn ($dt) => $dt->utc()->format('Ymd\THis\Z');
        $esc = fn (string $s) => str_replace(["\\", ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $s);
        $from = config('mail.from.address');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Fabtech//Eureka//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:'.$method,
            'BEGIN:VEVENT',
            'UID:'.$booking->uid.'@eureka',
            'SEQUENCE:'.(int) $booking->sequence,
            'DTSTAMP:'.$fmt(now()),
            'DTSTART:'.$fmt($booking->startsAt()),
            'DTEND:'.$fmt($booking->endsAt()),
            'SUMMARY:'.$esc($booking->title),
            'LOCATION:'.$esc($booking->room->name.' (Fabtech office)'),
            'ORGANIZER;CN='.$esc($booking->organizer->name).':mailto:'.$from,
            'ATTENDEE;CN='.$esc($booking->organizer->name).';ROLE=CHAIR;PARTSTAT=ACCEPTED:mailto:'.$booking->organizer->email,
        ];
        foreach ($booking->invitees() as $email) {
            $lines[] = 'ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:mailto:'.$email;
        }
        $lines[] = 'STATUS:'.($method === 'CANCEL' ? 'CANCELLED' : 'CONFIRMED');
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }
}
