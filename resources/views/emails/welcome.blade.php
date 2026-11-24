@extends('emails.layout')
@section('body')
<p style="margin:0 0 8px">Hi {{ $user->firstName() }}, you can now book meeting rooms at Fabtech with Eureka.</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;margin:8px 0 12px">
<tr><td style="padding:6px 16px 6px 0;color:#5E5E66">Sign in at</td><td style="font-weight:600"><a href="{{ url('/login') }}" style="color:#0F6FA6">{{ url('/login') }}</a></td></tr>
<tr><td style="padding:6px 16px 6px 0;color:#5E5E66">Employee ID</td><td style="font-weight:600">{{ $user->emp_id }}</td></tr>
<tr><td style="padding:6px 16px 6px 0;color:#5E5E66">Temporary password</td><td style="font-weight:600">{{ $temp }}</td></tr>
</table>
<p style="margin:0;color:#5E5E66;font-size:13px">You will choose your own password the first time you sign in.</p>
@endsection
