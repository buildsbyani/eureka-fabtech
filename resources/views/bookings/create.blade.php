@extends('layouts.app')
@section('title', 'Book '.$room->name)
@section('content')
@php $Clock = \App\Support\Clock::class; $me = auth()->user(); @endphp
<div style="max-width:620px" class="stack">
    <a href="{{ url()->previous() }}" class="small" style="text-decoration:none">‹ Back</a>
    <div>
        <div class="small">{{ $Clock::longDate($date) }}</div>
        <h1>{{ $room->name }}</h1>
        <div class="small">{{ $room->equipmentLine() }}{{ $room->senior_only ? ' · Senior staff only' : '' }}</div>
    </div>

    <div class="tile">
        <div class="lbl" style="margin-bottom:4px">Already booked on {{ $Clock::dayLabel($date) }}</div>
        @if ($dayBookings->isEmpty())
            <div class="small">Nothing yet. The room is free all day.</div>
        @else
            <div class="list">
                @foreach ($dayBookings as $b)
                    <div><span>{{ $b->timeRange() }}</span><span class="small">{{ $b->title }} · {{ $b->organizer->name }}</span></div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($start === null)
        <div class="msg err">No more slots today. Pick another day.</div>
    @else
    <form method="POST" action="{{ route('bookings.store') }}" class="tile stack">
        @csrf
        <input type="hidden" name="room_id" value="{{ $room->id }}">
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="field">
            <label class="lbl" for="title">Meeting title</label>
            <input id="title" name="title" value="{{ old('title') }}" placeholder="What is it for?" maxlength="120" required autofocus>
        </div>

        <div class="grid2 keep">
            <div class="field">
                <label class="lbl" for="start_min">Starts</label>
                <select id="start_min" name="start_min">
                    @foreach ($startOptions as $m)
                        <option value="{{ $m }}" @selected((int) old('start_min', $start) === $m)>{{ $Clock::time($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="lbl" for="duration">Lasts</label>
                <select id="duration" name="duration">
                    @foreach ([30, 60, 90, 120, 180] as $m)
                        <option value="{{ $m }}" @selected((int) old('duration', $duration) === $m)>{{ $Clock::duration($m) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($me->isStaff())
            <div class="field">
                <label class="lbl" for="organizer_id">Booking for</label>
                <select id="organizer_id" name="organizer_id">
                    @foreach ($people as $p)
                        <option value="{{ $p->id }}" @selected((int) old('organizer_id', $me->id) === $p->id)>{{ $p->name }}{{ $p->senior ? ' · Senior' : '' }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="field">
            <label class="lbl" for="invitees">Invite people <span style="font-weight:400">(optional, separate with commas)</span></label>
            <input id="invitees" name="invitees" value="{{ old('invitees') }}" list="directory" placeholder="name@company.com" autocomplete="off">
            <datalist id="directory">
                @foreach ($directory as $p)
                    <option value="{{ $p->email }}">{{ $p->name }}</option>
                @endforeach
            </datalist>
            <span class="small">Each person gets a calendar invite. Outside guests are fine too.</span>
        </div>

        <div class="field">
            <label class="lbl" for="repeat_weeks">Repeat</label>
            <select id="repeat_weeks" name="repeat_weeks">
                <option value="0">Does not repeat</option>
                @foreach ([2, 4, 8, 12] as $w)
                    <option value="{{ $w }}" @selected((int) old('repeat_weeks') === $w)>Every week for {{ $w }} weeks</option>
                @endforeach
            </select>
        </div>

        <button class="btn blue full">Book {{ $room->name }}</button>
        <p class="small" style="margin:0">We email the organiser {{ config('eureka.checkin_reminder_minutes') }} minutes before the start to confirm the room is still needed. If nobody confirms by {{ config('eureka.auto_release_minutes') }} minutes after the start, the room is released.</p>
    </form>
    @endif
</div>
@endsection
