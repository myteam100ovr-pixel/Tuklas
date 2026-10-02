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
                        <div class="meter-top"><span>{{ $m['label'] }}</span><b>{{ $m['pct'] }}%</b></div>
                        <div class="meter"><i style="width: {{ $m['pct'] }}%"></i></div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>