<div class="card-head">
    <h2>{{ __($chart['title']) }}</h2>
    <span class="chip">{{ __($chart['range']) }}</span>
</div>
<div class="chart-body">
    <div class="chart-side">
        <ul class="legend">
            @foreach ($chart['legend'] as $l)
                <li><i class="dot dot-{{ $l['tone'] }}"></i>{{ __($l['label']) }}</li>
            @endforeach
        </ul>
        <p class="insight">{{ __($chart['insight']) }}</p>
        @if (! empty($chart['cta']))
            <a class="btn btn-primary" href="{{ $chart['cta']['href'] }}">{{ __($chart['cta']['label']) }}</a>
            @if ($isYouth ?? false)<a class="workspace-preference-link" href="{{ route('youth.assessment.index') }}">{{ __('Update goals and learning preferences') }} ↗</a>@endif
        @endif
    </div>

    <div class="chart-main">
        @if ($chart['kind'] === 'bars')
            <div class="bars" role="img" aria-label="{{ __($chart['title']) }}">
                @foreach ($chart['bars'] as $b)
                    <span class="bar" style="--h: {{ $b['h'] }}%" title="{{ __($b['label']) }}: {{ $b['value'] }}"></span>
                @endforeach
            </div>
            <div class="bars-axis"><span>{{ $chart['axis'][0] }}</span><span>{{ $chart['axis'][1] }}</span></div>
        @else
            <ul class="meters">
                @foreach ($chart['items'] as $m)
                    <li>
                        <div class="meter-top"><span>{{ __($m['label']) }}</span><b>{{ isset($m['total']) && $m['total'] === 0 ? __('No data yet') : $m['pct'].'%' }}</b></div>
                        <div class="meter" role="meter" aria-label="{{ __($m['label']) }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $m['pct'] }}"><i style="width: {{ $m['pct'] }}%"></i></div>
                        @if (! empty($m['detail']))<p class="meter-detail">{{ __($m['detail']) }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
