@extends('emails.layout')
@section('body')
<p style="margin:0 0 8px">Hi {{ $user->firstName() }}, your Eureka password was reset by an admin.</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;margin:8px 0 12px">
<tr><td style="padding:6px 16px 6px 0;color:#5E5E66">Employee ID</td><td style="font-weight:600">{{ $user->emp_id }}</td></tr>
<tr><td style="padding:6px 16px 6px 0;color:#5E5E66">Temporary password</td><td style="font-weight:600">{{ $temp }}</td></tr>
</table>
<p style="margin:0;color:#5E5E66;font-size:13px">Sign in at <a href="{{ url('/login') }}" style="color:#0F6FA6">{{ url('/login') }}</a> and choose a new password. If you did not ask for this, tell your admin.</p>
@endsection
