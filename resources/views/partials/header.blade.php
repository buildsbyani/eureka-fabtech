{{-- Page header: small line, big title, profile button. --}}
<div class="between" style="margin-bottom:18px">
    <div>
        <div class="small">{{ $sub ?? \App\Support\Clock::longDate(\App\Support\Clock::today()) }}</div>
        <h1>{{ $title }}</h1>
    </div>
    <a class="av" href="{{ route('profile') }}" aria-label="Your profile">{{ auth()->user()->initials() }}</a>
</div>
