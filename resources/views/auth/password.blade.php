@extends('layouts.app')
@section('title', 'Password')
@section('content')
<div style="max-width:440px;margin:4vh auto 0">
    <form method="POST" action="{{ route('password.update') }}" class="tile stack">
        @csrf
        @method('PUT')
        <h2>{{ $first ? 'Set your password' : 'Change password' }}</h2>
        @if ($first)
            <p class="small" style="margin:0">You signed in with a temporary password. Choose your own to continue. Use at least 8 characters with letters and numbers.</p>
        @else
            <div class="field">
                <label class="lbl" for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
            </div>
        @endif
        <div class="field">
            <label class="lbl" for="password">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="field">
            <label class="lbl" for="password_confirmation">Type it again</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
        </div>
        <button class="btn full">Save password</button>
        @if ($first)
            <button class="btn ghost full" form="logout-form">Sign out</button>
        @else
            <a class="btn ghost full" href="{{ route('profile') }}">Cancel</a>
        @endif
    </form>
    <form id="logout-form" method="POST" action="{{ route('logout') }}">@csrf</form>
</div>
@endsection
