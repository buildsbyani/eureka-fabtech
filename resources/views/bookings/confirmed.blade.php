@extends('layouts.app')
@section('title', 'Room booked')
@section('content')
@php $me = auth()->user(); @endphp
<div style="max-width:520px;margin:4vh auto 0" class="stack">
    <div style="text-align:center">
        <div style="width:72px;height:72px;border-radius:50%;background:#E3F2EA;color:var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:34px;font-weight:700" aria-hidden="true">✓</div>
        <h1>Room booked</h1>
        <p class="small">
            {{ count($booking->invitees()) ? 'Calendar invites sent to '.count($booking->invitees()).' '.(count($booking->invitees()) === 1 ? 'person' : 'people').'.' : 'Confirmation emailed.' }}
            @if ($count > 1) {{ $count }} weekly meetings booked. @endif
            @if (! empty($skipped)) Skipped {{ implode(', ', $skipped) }} because the room was taken. @endif
        </p>
    </div>
    <div class="tile">
        <div style="font-weight:700;font-size:17px">{{ $booking->title }}</div>
        <div class="list" style="margin-top:6px">
            <div><span class="small">Room</span><b>{{ $booking->room->name }}</b></div>
            <div><span class="small">When</span><b>{{ \App\Support\Clock::longDate($booking->dateString()) }} · {{ $booking->timeRange() }}</b></div>
            <div><span class="small">For</span><b>{{ $booking->organizer->name }}</b></div>
            @if (count($booking->invitees()))
                <div><span class="small">Invited</span><b style="text-align:right">{!! collect($booking->invitees())->map(fn ($e) => e($e))->implode('<br>') !!}</b></div>
            @endif
        </div>
    </div>
    <a class="btn full" href="{{ $me->isStaff() ? route('reception.today', ['date' => $booking->dateString()]) : route('bookings.mine') }}">{{ $me->isStaff() ? 'Back to the timeline' : 'View my bookings' }}</a>
    <a class="btn ghost full" href="{{ route('bookings.ics', $booking) }}">Add to my calendar</a>
</div>
@endsection
