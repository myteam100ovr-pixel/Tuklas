<x-app-layout>
    <div class="dash-title"><h1>{{ $title }}</h1></div>

    <div class="dash">
        <div class="dash-left">
            <section class="stats" aria-label="Summary">
                @foreach ($stats as $s)
                    <article class="card stat">
                        <div class="stat-top">
                            <span class="stat-label">{{ $s['label'] }}</span>
                            @if (! empty($s['delta']))<span class="chip chip-{{ $s['tone'] }}">{{ $s['delta'] }}</span>@endif
                        </div>
                        <div class="stat-bot">
                            <strong class="stat-val">{{ $s['value'] }}</strong>
                            <span class="stat-note">{{ $s['note'] }}</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="card chart">@include('dashboard.partials.chart')</section>
            @isset($profileInsights)
                <section class="card profile-insights" aria-labelledby="profile-insights-title">
                    <div class="profile-insights__heading">
                        <div><span class="stat-label">From your profile and latest scan</span><h2 id="profile-insights-title">Your career insights</h2></div>
                        <a href="{{ route('scanner.index') }}">Scan a document</a>
                    </div>
                    <div class="profile-insights__groups">
                        @foreach ([
                            'skills' => 'Skills',
                            'credentials' => 'Qualifications and certificates',
                            'job_roles' => 'Suggested job roles',
                            'tesda_training' => 'TESDA training to consider',
                        ] as $key => $label)
                            <section class="profile-insights__group">
                                <h3>{{ $label }}</h3>
                                @if (count($profileInsights[$key]) > 0)
                                    <ul>
                                        @foreach ($profileInsights[$key] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p>Nothing saved yet.</p>
                                @endif
                            </section>
                        @endforeach
                    </div>
                </section>
            @endisset
            <section class="card tbl">@include('dashboard.partials.table')</section>
        </div>

        <aside class="dash-right">
            <section class="card tasks">@include('dashboard.partials.tasks')</section>
            @includeFirst(['dashboard.partials.assistant', 'dashboard.partials.quick'])
            <x-career-chat />
        </aside>
    </div>
</x-app-layout>
