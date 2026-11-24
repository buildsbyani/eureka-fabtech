@extends('emails.layout')
@section('body')
<p style="margin:0 0 4px">Hi {{ $booking->organizer->firstName() }}, your room is booked{{ $by->id !== $booking->organizer_id ? ' by '.$by->name : '' }}.</p>
@include('emails._details')
@if ($count > 1)<p style="margin:0 0 8px">It repeats every week: {{ $count }} meetings in total.</p>@endif
@if (! empty($skipped))<p style="margin:0 0 8px;color:#8E2A22">Skipped {{ implode(', ', $skipped) }} because the room was already taken.</p>@endif
@if (count($booking->invitees()))<p style="margin:0 0 8px">Invited: {{ implode(', ', $booking->invitees()) }}</p>@endif
<p style="margin:0;color:#5E5E66;font-size:13px">A calendar invite is attached. We will email you {{ config('eureka.checkin_reminder_minutes') }} minutes before the start to check you still need the room.</p>
@endsection
