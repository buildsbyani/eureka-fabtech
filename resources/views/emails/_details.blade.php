<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:12px 0">
    <tr><td style="padding:9px 0;color:#5E5E66;width:100px;border-top:1px solid #F0F0EE">Meeting</td><td style="padding:9px 0;font-weight:600;border-top:1px solid #F0F0EE">{{ $booking->title }}</td></tr>
    <tr><td style="padding:9px 0;color:#5E5E66;border-top:1px solid #F0F0EE">Room</td><td style="padding:9px 0;font-weight:600;border-top:1px solid #F0F0EE">{{ $booking->room->name }} · {{ $booking->room->capacity }} seats</td></tr>
    <tr><td style="padding:9px 0;color:#5E5E66;border-top:1px solid #F0F0EE">When</td><td style="padding:9px 0;font-weight:600;border-top:1px solid #F0F0EE">{{ \App\Support\Clock::longDate($booking->dateString()) }} · {{ $booking->timeRange() }}</td></tr>
    <tr><td style="padding:9px 0;color:#5E5E66;border-top:1px solid #F0F0EE;border-bottom:1px solid #F0F0EE">Organiser</td><td style="padding:9px 0;font-weight:600;border-top:1px solid #F0F0EE;border-bottom:1px solid #F0F0EE">{{ $booking->organizer->name }}</td></tr>
</table>
