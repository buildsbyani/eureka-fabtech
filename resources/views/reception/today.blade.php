@extends('layouts.app')
@section('title', 'Today')
@section('content')
@php $Clock = \App\Support\Clock::class; $isToday = $date === $Clock::today(); @endphp
@include('partials.header', [
    'title' => $isToday ? 'Today' : $Clock::longDate($date),
    'sub' => $Clock::longDate($date).' · '.$total.' bookings'.($freeNow !== null ? ' · '.$freeNow.' rooms free now' : ''),
])

<div class="stack">
    <div class="between wrap">
        <div class="row" style="gap:6px">
            @if ($prev)<a class="chip" href="{{ route('reception.today', ['date' => $prev]) }}" aria-label="Previous day">‹</a>@endif
            <a class="chip on" href="{{ route('reception.today') }}">{{ $Clock::dayLabel($date) }}</a>
            <a class="chip" href="{{ route('reception.today', ['date' => $next]) }}" aria-label="Next day">›</a>
        </div>
        <a class="btn blue sm" href="{{ route('rooms.index', ['date' => $date]) }}">New booking</a>
    </div>

    @if ($peek)
        <div class="tile row wrap" style="justify-content:space-between">
            <div>
                <div class="small">{{ $peek->room->name }} · {{ $peek->timeRange() }}</div>
                <b>{{ $peek->title }}</b>
                <div class="small">For {{ $peek->organizer->name }}{{ count($peek->invitees()) ? ' · '.count($peek->invitees()).' invited' : '' }}{{ $peek->checked_in_at ? ' · Confirmed' : '' }}</div>
            </div>
            <div class="row" style="gap:6px">
                @if ($peek->status === 'active')
                    <form method="POST" action="{{ route('bookings.cancel', $peek) }}" class="inline">@csrf<button class="btn sm ghost">Cancel booking</button></form>
                @endif
                <a class="btn sm ghost" href="{{ route('reception.today', ['date' => $date]) }}">Close</a>
            </div>
        </div>
    @else
        <p class="small" style="margin:0">Tap an empty slot to book it for someone. Tap a booking to see or cancel it.</p>
    @endif

    <div class="tl">
        <table>
            <thead>
                <tr>
                    <th style="width:150px;border-right:1px solid var(--line)"><span class="sr">Room</span></th>
                    @foreach ($hours as $h)
                        <th colspan="2">{{ str_replace(':00', '', $Clock::time($h)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($grid as $row)
                    @php $room = $row['room']; @endphp
                    <tr>
                        <td class="rn {{ $room->senior_only ? 'senior' : '' }}">{{ $room->name }}<div class="small" style="font-weight:500">{{ $room->capacity }} seats{{ $room->senior_only ? ' · Seniors' : '' }}</div></td>
                        @foreach ($row['cells'] as $cell)
                            @if ($cell['type'] === 'booking')
                                @php $b = $cell['booking']; $done = $isToday && $b->end_min <= $Clock::nowMinutes(); @endphp
                                <td colspan="{{ $cell['span'] }}">
                                    <a class="bk {{ $done ? 'done' : ($room->senior_only ? 'senior' : '') }}" href="{{ route('reception.today', ['date' => $date, 'booking' => $b->id]) }}" title="{{ $b->title }}, {{ $b->timeRange() }}">
                                        <b>{{ $b->title }}</b><br>{{ $b->organizer->name }}{{ $b->checked_in_at || $done ? '' : ' · not confirmed' }}
                                    </a>
                                </td>
                            @elseif ($cell['type'] === 'past')
                                <td class="past"></td>
                            @else
                                <td><a class="free" href="{{ route('bookings.create', ['room' => $room->id, 'date' => $date, 'start' => $cell['start'], 'dur' => 60]) }}" aria-label="Book {{ $room->name }} at {{ $Clock::time($cell['start']) }}"></a></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="row small wrap" style="gap:16px">
        <span class="row" style="gap:6px"><span class="dot" style="background:#9FCDE8"></span>Booking</span>
        <span class="row" style="gap:6px"><span class="dot" style="background:#EFC87A"></span>Senior room</span>
        <span class="row" style="gap:6px"><span class="dot" style="background:#C9CED6"></span>Finished</span>
    </div>
</div>
@endsection
