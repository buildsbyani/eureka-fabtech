@extends('layouts.app')
@section('title', 'My bookings')
@section('content')
@php $Clock = \App\Support\Clock::class; $me = auth()->user(); $askId = (int) request('ask'); @endphp
@include('partials.header', ['title' => 'My bookings'])

<div class="stack">
    @foreach ($ending as $last)
        <div class="tile navy row wrap" style="justify-content:space-between">
            <p style="margin:0;color:#C9D6E4"><b style="color:#fff">{{ $last->title }}</b> stops repeating after {{ $last->date->format('j F') }}. Still need it?</p>
            <form method="POST" action="{{ route('bookings.extend', $last) }}">@csrf<button class="btn sm" style="background:#fff;color:var(--navy)">Extend 3 months</button></form>
        </div>
    @endforeach

    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:4px;padding:4px;border-radius:16px;background:#EBEBE8" role="tablist">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label)
            <a role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" href="{{ route('bookings.mine', ['tab' => $key]) }}" class="chip" style="border:0;border-radius:12px;{{ $tab === $key ? 'background:#fff;color:var(--ink);font-weight:700' : 'background:transparent' }}">{{ $label }}</a>
        @endforeach
    </div>

    @forelse ($list as $b)
        <div class="tile" style="padding:14px 18px">
            <div class="row" style="align-items:flex-start">
                <div style="width:64px;flex:none">
                    <div style="font-weight:700;font-size:17px">{{ \Illuminate\Support\Str::before($Clock::time($b->start_min), ' ') }}</div>
                    <div class="small">{{ \Illuminate\Support\Str::after($Clock::time($b->start_min), ' ') }}</div>
                </div>
                <div style="flex:1">
                    <div class="small">{{ $b->dayLabel() }} · {{ $b->room->name }} · {{ $b->timeRange() }}</div>
                    <div style="font-weight:700">{{ $b->title }}</div>
                    @if ($b->organizer_id !== $me->id)
                        <div style="font-size:12px;font-weight:700;color:var(--blue)">Booked for {{ $b->organizer->name }}</div>
                    @elseif ($b->booked_by_id !== $me->id)
                        <div style="font-size:12px;font-weight:700;color:var(--blue)">Booked for you by {{ $b->bookedBy->name }}</div>
                    @endif
                    @if ($b->isRecurring())<div style="font-size:12px;font-weight:700;color:var(--mute)">Repeats weekly</div>@endif
                    @if ($b->status === 'released')<div style="font-size:12px;color:#8E2A22">Released. Nobody confirmed in time.</div>@endif
                    @if ($b->status === 'cancelled')<div style="font-size:12px;color:#8E2A22">Cancelled</div>@endif
                </div>
                @if ($tab === 'upcoming')
                    <a class="chip" href="{{ route('bookings.mine', ['ask' => $b->id]) }}" aria-label="Cancel {{ $b->title }}" style="width:40px;padding:0">✕</a>
                @endif
            </div>
            @if ($tab === 'upcoming' && $b->canCheckIn())
                <form method="POST" action="{{ route('bookings.checkin', $b) }}" style="margin-top:10px">@csrf<button class="btn sm green">I am using the room</button></form>
            @endif
            @if ($askId === $b->id)
                <div class="between wrap" style="margin-top:10px;padding-top:10px;border-top:1px solid var(--line2)">
                    <span style="font-weight:600;color:#8E2A22">Cancel this booking?</span>
                    <span class="row" style="gap:6px">
                        <a class="btn sm ghost" href="{{ route('bookings.mine') }}">Keep</a>
                        @if ($b->isRecurring())
                            <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="inline">@csrf<input type="hidden" name="series" value="1"><button class="btn sm ghost">All in series</button></form>
                        @endif
                        <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="inline">@csrf<button class="btn sm red">Yes, cancel</button></form>
                    </span>
                </div>
            @endif
        </div>
    @empty
        <div class="tile small">{{ ['upcoming' => 'Nothing booked yet. Book a room from Home.', 'past' => 'No past bookings yet.', 'cancelled' => 'Nothing cancelled.'][$tab] }}</div>
    @endforelse
</div>
@endsection
