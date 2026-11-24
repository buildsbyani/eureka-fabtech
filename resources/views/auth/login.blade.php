@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<div style="max-width:440px;margin:4vh auto 0">
    <div class="tile navy" style="padding:28px 24px 44px;border-radius:28px 28px 0 0">
        <div style="font-size:28px;font-weight:700;letter-spacing:.14em">EUREKA</div>
        <div style="color:#A9BDD1;margin-top:4px">Meeting rooms at Fabtech</div>
    </div>
    <form method="POST" action="{{ route('login.attempt') }}" class="tile stack" style="margin-top:-24px;border-radius:28px;padding:26px 22px">
        @csrf
        <h2>Sign in</h2>
        <div class="field">
            <label class="lbl" for="emp_id">Employee ID</label>
            <input id="emp_id" name="emp_id" value="{{ old('emp_id') }}" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label class="lbl" for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <label class="row lbl" style="gap:8px"><input type="checkbox" name="remember" value="1" style="width:18px;height:18px;accent-color:#072A4A"> Keep me signed in on this device</label>
        <button class="btn full">Sign in</button>
        <p class="small" style="margin:0;text-align:center">First time here? Your temporary password is in the welcome email. Forgot it? Ask your admin to reset it.</p>
    </form>
</div>
@endsection
