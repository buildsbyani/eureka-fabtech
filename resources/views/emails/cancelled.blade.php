@extends('emails.layout')
@section('body')
<p style="margin:0 0 4px">{{ $count > 1 ? $count.' meetings in this weekly series were cancelled' : 'This meeting was cancelled' }}{{ $by->id !== $booking->organizer_id ? ' by '.$by->name : '' }}.</p>
@include('emails._details')
<p style="margin:0;color:#5E5E66;font-size:13px">The room is free for others now.</p>
@endsection
