@extends('emails.layout')
@section('body')
<p style="margin:0 0 4px">Hi {{ $booking->organizer->firstName() }}, your meeting in <b>{{ $booking->room->name }}</b> starts at {{ \App\Support\Clock::time($booking->start_min) }}. Do you still need the room?</p>
@include('emails._details')
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 12px"><tr>
<td style="padding-right:8px"><a href="{{ $keepUrl }}" style="display:inline-block;background:#1F8A5B;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:12px">Yes, I need it</a></td>
<td><a href="{{ $releaseUrl }}" style="display:inline-block;background:#FFFFFF;color:#3F3F46;text-decoration:none;font-weight:600;padding:11px 17px;border-radius:12px;border:1.5px solid #D9D9D5">No, release it</a></td>
</tr></table>
<p style="margin:0;color:#6B4500;font-size:13px;background:#FBF1DC;padding:10px 12px;border-radius:10px">If nobody confirms by {{ $deadline }}, the booking is cancelled and the room becomes free to book.</p>
@endsection
