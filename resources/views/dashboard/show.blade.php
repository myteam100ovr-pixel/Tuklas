<x-app-layout>
    @php
        $isYouth = isset($profileInsights);
        $currentQualifications = collect($nationalCatalog['qualifications'])->where('status', 'current');
    @endphp
    <div class="workspace" id="overview">
        <div class="workspace-main">
            <header class="workspace-heading">
                <div><p class="workspace-eyebrow">YOUR WORKSPACE, AT A GLANCE</p><h1>{{ $title }}</h1><p class="workspace-heading-description">{{ $isYouth ? 'Your skills, your progress, and what comes next.' : 'A clear view of your community and the work ahead.' }}</p></div>
                <span class="workspace-date">{{ now()->timezone('Asia/Manila')->format('l, M j') }}</span>
            </header>
            <section class="workspace-stats" aria-label="Your overview">
                @foreach ($stats as $stat)
                    <article><div><span>{{ $stat['label'] }}</span><span class="workspace-stat-number">0{{ $loop->iteration }}</span></div><strong>{{ $stat['value'] }}</strong><p>{{ $stat['note'] }} @if (! empty($stat['delta']))<em>{{ $stat['delta'] }}</em>@endif</p></article>
                @endforeach
            </section>
            <section class="workspace-focus">
                <div class="workspace-focus-icon"><svg class="ic" aria-hidden="true"><use href="#{{ $isYouth ? 'i-scan' : 'i-cap' }}"/></svg></div>
                <div><span class="workspace-eyebrow">{{ $isYouth ? 'BUILD YOUR NEXT STEP' : 'CONNECT PEOPLE TO OPPORTUNITY' }}</span><h2>{{ $isYouth ? 'Put your experience to work.' : 'Make room for new possibilities.' }}</h2><p>{{ $isYouth ? 'Scan a resume or certificate to explore skills and career recommendations.' : 'Explore qualifications and help your community find a training path.' }}</p></div>
                <a class="workspace-primary" href="{{ $isYouth ? route('scanner.index') : route('tesda.index') }}">{{ $isYouth ? 'Start a scan' : 'Explore training' }} <span aria-hidden="true">↗</span></a>
            </section>
            <nav class="workspace-sections" aria-label="Dashboard sections">
                <a href="#progress">Progress</a><a href="#next-steps">Next steps</a>
                @if ($isYouth)<a href="#career-insights">Career insights</a>@endif
                <a href="#recent-activity">Activity</a><a href="#training-library">Training</a><a href="#workspace-assistant">Ask Tuklas ↗</a>
            </nav>
            <div class="workspace-columns">
                <div class="workspace-content">
                    <section class="workspace-panel workspace-progress" id="progress">@include('dashboard.partials.chart')</section>
                    <section class="workspace-panel tbl" id="recent-activity">@include('dashboard.partials.table')</section>
                    @if ($isYouth)<div id="career-insights">@include('dashboard.partials.insights')</div>@endif
                    <section class="workspace-library workspace-panel" id="training-library">
                        <header class="workspace-section-heading"><div><p class="workspace-eyebrow">MORE WAYS FORWARD</p><h2>Find your training path.</h2></div><a href="{{ route('tesda.index') }}#nc-offerings">View all ↗</a></header>
                        <p class="workspace-muted">{{ $currentQualifications->count() }} national qualifications across NC I–IV. Explore the official standards, then find a registered provider.</p>
                        <div class="workspace-levels">
                            @foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)
                                <a href="{{ route('tesda.index', ['level' => $level]) }}#nc-offerings"><span>{{ $level }}</span><strong>{{ $currentQualifications->where('nc_level', $level)->count() }}</strong><small>qualifications <b>↗</b></small></a>
                            @endforeach
                        </div>
                        <p class="workspace-footnote">National catalog · Local schedules, slots, and entry requirements depend on the provider.</p>
                    </section>
                </div>
                <aside class="workspace-side">
                    <section class="workspace-panel tasks" id="next-steps">@include('dashboard.partials.tasks')</section>
                    <section class="workspace-panel workspace-shortcuts"><p class="workspace-eyebrow">QUICK ACCESS</p><h2>Keep moving.</h2><div>@foreach ($actions as $action)@if ($action['href'])<a href="{{ $action['href'] }}"><svg class="ic" aria-hidden="true"><use href="#i-{{ $action['icon'] }}"/></svg>{{ $action['label'] }}<span>↗</span></a>@else<span class="workspace-unavailable">{{ $action['label'] }} <small>Coming soon</small></span>@endif @endforeach</div></section>
                    <div id="workspace-assistant"><x-career-chat /></div>
                </aside>
            </div>
            <footer class="workspace-footer"><span>tuklas / your next possibility</span><a href="{{ route('tesda.index') }}">Official TESDA resources ↗</a></footer>
        </div>
    </div>
</x-app-layout>
