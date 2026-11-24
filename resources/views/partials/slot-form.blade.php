{{-- Date, start, duration and group size. Plain GET form: works without JavaScript. --}}
<form method="GET" action="{{ $action }}" class="stack" id="slot-form">
    <div class="between">
        <span class="lbl">{{ $q['days']['label'] }}</span>
        <div class="row" style="gap:6px">
            @if ($q['days']['prev'])
                <a class="chip" href="{{ $action }}?{{ http_build_query(['date' => $q['days']['prev'], 'dur' => $q['duration'], 'size' => $q['size']]) }}" aria-label="Earlier dates">‹</a>
            @else
                <span class="chip" aria-hidden="true" style="opacity:.35">‹</span>
            @endif
            @if ($q['days']['next'])
                <a class="chip" href="{{ $action }}?{{ http_build_query(['date' => $q['days']['next'], 'dur' => $q['duration'], 'size' => $q['size']]) }}" aria-label="Later dates">›</a>
            @endif
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:6px" role="radiogroup" aria-label="Date">
        @foreach ($q['days']['list'] as $d)
            <label class="chip-radio" style="display:block">
                <input type="radio" name="date" value="{{ $d['date'] }}" @checked($q['date'] === $d['date']) onchange="this.form.submit()">
                <span class="day" style="width:100%;justify-content:center;display:flex"><small>{{ $d['top'] }}</small><b>{{ $d['num'] }}</b></span>
            </label>
        @endforeach
    </div>
    <div class="grid2 keep">
        <div class="field">
            <label class="lbl" for="start">Starts</label>
            <select id="start" name="start" onchange="this.form.submit()">
                @foreach ($q['startOptions'] as $m)
                    <option value="{{ $m }}" @selected($m === $q['start'])>{{ \App\Support\Clock::time($m) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <span class="lbl">Lasts</span>
            <div class="chips" role="radiogroup" aria-label="Duration">
                @foreach ([30 => '30 min', 60 => '1 hr', 120 => '2 hr'] as $val => $label)
                    <label class="chip-radio"><input type="radio" name="dur" value="{{ $val }}" @checked($q['duration'] === $val) onchange="this.form.submit()"><span style="padding:0 11px">{{ $label }}</span></label>
                @endforeach
            </div>
        </div>
    </div>
    <div class="field">
        <span class="lbl">How many people?</span>
        <div class="chips" role="radiogroup" aria-label="Group size">
            @foreach (array_keys(config('eureka.group_sizes')) as $i => $label)
                <label class="chip-radio"><input type="radio" name="size" value="{{ $i }}" @checked($q['size'] === $i) onchange="this.form.submit()"><span>{{ $label }}</span></label>
            @endforeach
        </div>
    </div>
    <noscript><button class="btn ghost">Update</button></noscript>
</form>
