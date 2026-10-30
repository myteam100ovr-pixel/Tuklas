<div class="card-head">
    <h2>{{ $chart['title'] }}</h2>
    <span class="chip">{{ $chart['range'] }}</span>
</div>
<div class="chart-body">
    <div class="chart-side">
        <ul class="legend">
            @foreach ($chart['legend'] as $l)
                <li><i class="dot dot-{{ $l['tone'] }}"></i>{{ $l['label'] }}</li>
            @endforeach
        </ul>
        <p class="insight">{{ $chart['insight'] }}</p>
        @if (! empty($chart['cta']))
            <a class="btn btn-primary" href="{{ $chart['cta']['href'] }}">{{ $chart['cta']['label'] }}</a>
            @if ($isYouth ?? false)<a class="workspace-preference-link" href="{{ route('youth.assessment.index') }}">Update goals and learning preferences ↗</a>@endif
        @endif
    </div>

    <div class="chart-main">
        @if ($chart['kind'] === 'bars')
            <div class="bars" role="img" aria-label="{{ $chart['title'] }}">
                @foreach ($chart['bars'] as $b)
                    <span class="bar" style="--h: {{ $b['h'] }}%" title="{{ $b['label'] }}: {{ $b['value'] }}"></span>
                @endforeach
            </div>
            <div class="bars-axis"><span>{{ $chart['axis'][0] }}</span><span>{{ $chart['axis'][1] }}</span></div>
        @else
            <ul class="meters">
                @foreach ($chart['items'] as $m)
                    <li>
                        <div class="meter-top"><span>{{ $m['label'] }}</span><b>{{ isset($m['total']) && $m['total'] === 0 ? 'No data yet' : $m['pct'].'%' }}</b></div>
                        <div class="meter" role="meter" aria-label="{{ $m['label'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $m['pct'] }}"><i style="width: {{ $m['pct'] }}%"></i></div>
                        @if (! empty($m['detail']))<p class="meter-detail">{{ $m['detail'] }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
