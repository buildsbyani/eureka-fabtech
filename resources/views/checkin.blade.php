@extends('layouts.app')
@section('title', 'Check-in')
@section('content')
<div style="max-width:480px;margin:8vh auto 0" class="tile stack">
    <div style="font-weight:700;letter-spacing:.14em;color:var(--navy)">EUREKA</div>
    <h2>{{ $message }}</h2>
    <div class="small">{{ $booking->title }} · {{ $booking->room->name }} · {{ $booking->dayLabel() }}, {{ $booking->timeRange() }}</div>
    <a class="btn full" href="{{ route('login') }}">Open Eureka</a>
</div>
@endsection
