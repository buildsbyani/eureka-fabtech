@extends('emails.layout')
@section('body')
<p style="margin:0 0 4px">{{ $booking->organizer->name }} invited you to a meeting at Fabtech.</p>
@include('emails._details')
<p style="margin:0;color:#5E5E66;font-size:13px">Open the attached invite to add it to your calendar and reply.</p>
@endsection
