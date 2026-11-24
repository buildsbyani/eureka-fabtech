@extends('layouts.app')
@section('title', 'Home')
@section('content')
@php $Clock = \App\Support\Clock::class; $me = auth()->user(); @endphp
@include('partials.header', ['title' => $greeting.', '.$me->firstName()])

<div class="stack">
    <section class="tile stack" aria-label="Book a room">
        <h2 style="font-size:24px">Need a room?</h2>
        @include('partials.slot-form', ['action' => route('home'), 'q' => $q])

        @if ($best)
            <p style="margin:0;color:var(--soft)">
                {{ $fits->count() === 1 ? 'Only '.$best['room']->name.' fits.' : $fits->count().' rooms fit. '.$best['room']->name.' ('.$best['room']->capacity.' seats) is the best match.' }}
            </p>
            <a class="btn blue full" href="{{ route('bookings.create', ['room' => $best['room']->id, 'date' => $q['date'], 'start' => $q['start'], 'dur' => $q['duration']]) }}">
                Book {{ $best['room']->name }} for {{ $Clock::duration($q['duration']) }} →
            </a>
        @else
            <p style="margin:0;color:var(--soft)">No room fits that. Try a shorter slot or another time.</p>
            <a class="btn full" href="{{ route('rooms.index', ['date' => $q['date'], 'start' => $q['start'], 'dur' => $q['duration'], 'size' => $q['size']]) }}">See all rooms →</a>
        @endif
    </section>

    <div class="grid2 keep">
        <a class="tile" href="{{ route('rooms.index') }}" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:12px;min-height:150px">
            <span class="lbl"><span class="dot" style="background:var(--green)"></span> Free right now</span>
            <span class="big">{{ $freeNow }}</span>
            <span class="small">of {{ $roomCount }} rooms · See them →</span>
        </a>
        @if ($next)
            <a class="tile navy" href="{{ route('bookings.mine') }}" style="text-decoration:none;display:flex;flex-direction:column;gap:8px;min-height:150px">
                <span style="font-size:13px;color:#A9BDD1;font-weight:600">Your next meeting</span>
                <span style="font-size:28px;font-weight:700;letter-spacing:-.03em">{{ $Clock::time($next->start_min) }}</span>
                <span style="font-size:13px;color:var(--coral2);font-weight:700">{{ $next->dayLabel() }}</span>
                <span style="font-size:13px;color:#C9D6E4">{{ $next->title }}<br>{{ $next->room->name }}</span>
            </a>
        @else
            <div class="tile navy" style="display:flex;flex-direction:column;gap:8px;min-height:150px">
                <span style="font-size:13px;color:#A9BDD1;font-weight:600">Your next meeting</span>
                <span style="font-size:18px;font-weight:700">Nothing booked</span>
                <span style="font-size:13px;color:#C9D6E4">Your bookings show up here.</span>
            </div>
        @endif
    </div>
</div>
@endsection
