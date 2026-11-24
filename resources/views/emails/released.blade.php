@extends('emails.layout')
@section('body')
<p style="margin:0 0 4px">Hi {{ $booking->organizer->firstName() }}, your booking was released. {{ $reason }}</p>
@include('emails._details')
<p style="margin:0;color:#5E5E66;font-size:13px">If you still need a room, book again in Eureka. It may have been taken.</p>
@endsection
