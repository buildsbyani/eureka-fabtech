@extends('layouts.app')
@section('title', 'Find a room')
@section('content')
@php $Clock = \App\Support\Clock::class; @endphp
@include('partials.header', ['title' => 'Find a room'])

<div class="stack">
    <section class="tile">@include('partials.slot-form', ['action' => route('rooms.index'), 'q' => $q])</section>

    <div class="between">
        <h2>{{ $freeCount === 1 ? '1 room free' : $freeCount.' rooms free' }}</h2>
        <span class="small">{{ $Clock::dayLabel($q['date']) }}, {{ $Clock::range($q['start'], $q['end']) }}</span>
    </div>

    <div class="stack" style="gap:8px">
        @forelse ($rows as $row)
            @php $room = $row['room']; @endphp
            @if ($row['state'] === 'free')
                <a class="room-row free" href="{{ route('bookings.create', ['room' => $room->id, 'date' => $q['date'], 'start' => $q['start'], 'dur' => $q['duration']]) }}">
                    <span class="dot" style="background:var(--green)"></span>
                    <span style="flex:1;display:flex;flex-direction:column"><span class="small">{{ $room->capacity }} seats · {{ $row['note'] }}</span><span class="name">{{ $room->name }}</span></span>
                    <span style="color:var(--blue);font-weight:700">Book →</span>
                </a>
            @elseif ($row['state'] === 'restricted')
                <div class="room-row restricted">
                    <span class="dot" style="background:var(--amber)"></span>
                    <span style="flex:1;display:flex;flex-direction:column"><span class="small">{{ $room->capacity }} seats · {{ $row['note'] }}</span><span class="name">{{ $room->name }}</span></span>
                    <span style="color:var(--amber);font-weight:700;font-size:13px">Restricted</span>
                </div>
            @else
                <div class="room-row busy">
                    <span class="dot" style="background:#A1A1AA"></span>
                    <span style="flex:1;display:flex;flex-direction:column"><span class="small">{{ $room->capacity }} seats · {{ $row['note'] }}</span><span class="name">{{ $room->name }}</span></span>
                </div>
            @endif
        @empty
            <div class="tile small">No room fits that many people. Try a smaller group.</div>
        @endforelse
    </div>
</div>
@endsection
