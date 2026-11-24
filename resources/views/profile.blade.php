@extends('layouts.app')
@section('title', 'Profile')
@section('content')
@include('partials.header', ['title' => 'Profile', 'sub' => $user->roleLabel()])
<div class="stack" style="max-width:520px">
    <div class="tile">
        <b style="font-size:18px">{{ $user->name }}</b>
        <div class="small">{{ $user->emp_id }} · {{ $user->email }}{{ $user->department ? ' · '.$user->department : '' }}</div>
    </div>
    <a class="btn ghost full" href="{{ route('password.edit') }}">Change password</a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn full" style="background:#fff;color:#B3352B;border:1.5px solid #E8C4C0">Sign out</button></form>
</div>
@endsection
