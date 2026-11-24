<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#072A4A">
    <title>@yield('title', 'Eureka') · Eureka</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/eureka.css') }}">
</head>
<body>
@php
    $me = auth()->user();
    $nav = [];
    if ($me) {
        $nav = match ($me->role) {
            'admin' => [['reception.today', 'Today'], ['rooms.index', 'Rooms'], ['admin.users.index', 'Users'], ['admin.rooms.index', 'Room setup']],
            'reception' => [['reception.today', 'Today'], ['rooms.index', 'Rooms'], ['bookings.mine', 'Bookings']],
            default => [['home', 'Home'], ['rooms.index', 'Rooms'], ['bookings.mine', 'Bookings']],
        };
    }
@endphp
<div class="shell">
    @if ($me && ! $me->must_change_password)
        <aside class="side" aria-label="Main">
            <div class="row" style="padding:0 8px 18px"><span style="font-weight:700;letter-spacing:.14em;color:var(--navy)">EUREKA</span><span class="small">by Fabtech</span></div>
            @foreach ($nav as [$route, $label])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'on' : '' }}" style="margin-top:auto">{{ $me->name }}</a>
        </aside>
    @endif
    <main>
        @if (session('ok'))
            <div class="msg ok" role="status" style="margin-bottom:14px">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="msg err" role="alert" style="margin-bottom:14px">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>
@if ($me && ! $me->must_change_password)
    <nav class="nav" aria-label="Main">
        @foreach ($nav as [$route, $label])
            <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
@endif
</body>
</html>
