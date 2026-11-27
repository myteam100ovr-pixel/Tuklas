<x-app-layout>
    @php
        $isYouth = isset($profileInsights);
        $currentQualifications = collect($nationalCatalog['qualifications'])->where('status', 'current');
    @endphp
    <div class="workspace" id="overview">
        <div class="workspace-main">
            <header class="workspace-heading">
                <div><p class="workspace-eyebrow">{{ __('YOUR WORKSPACE, AT A GLANCE') }}</p><h1>{{ $title }}</h1><p class="workspace-heading-description">{{ $isYouth ? 'Your skills, your progress, and what comes next.' : 'A clear view of your community and the work ahead.' }}</p></div>
                <span class="workspace-date">{{ now()->timezone('Asia/Manila')->format('l, M j') }}</span>
            </header>
            <section class="workspace-get-started" aria-labelledby="workspace-get-started-title">
                <div><p class="workspace-eyebrow">{{ __('START HERE') }}</p><h2 id="workspace-get-started-title">{{ __('What do you want to do?') }}</h2><p>{{ __('Choose one task. You can come back to this page at any time.') }}</p></div>
                <div class="workspace-get-started__links">
                    @switch(auth()->user()->role)
                        @case(\App\Enums\Role::Admin)
                            <a href="{{ route('admin.users.index') }}">{{ __('Manage people') }} <span>↗</span></a>
                            <a href="{{ route('opportunities.index') }}">{{ __('Post a job') }} <span>↗</span></a>
                            <a href="{{ route('applications.index') }}">{{ __('Review training requests') }} <span>↗</span></a>
                            @break
                        @case(\App\Enums\Role::TesdaAdmin)
                            <a href="{{ route('centers.index') }}">{{ __('Manage training centers') }} <span>↗</span></a>
                            <a href="{{ route('trainer.programs.index') }}">{{ __('Manage programs') }} <span>↗</span></a>
                            <a href="{{ route('center-trainers.index') }}">{{ __('Assign trainers') }} <span>↗</span></a>
                            <a href="{{ route('sessions.index') }}">{{ __('Set training dates') }} <span>↗</span></a>
                            <a href="{{ route('applications.index') }}">{{ __('Review training requests') }} <span>↗</span></a>
                            <a href="{{ route('assessments.index') }}">{{ __('Record assessments') }} <span>↗</span></a>
                            <a href="{{ route('messages.index') }}">{{ __('Messages') }} <span>↗</span></a>
                            @break
                        @default
                            <a href="{{ route('youth.assessment.index') }}">{{ __('Take the skills assessment') }} <span>↗</span></a>
                            <a href="{{ route('profile.show') }}">{{ __('Complete my profile') }} <span>↗</span></a>
                            <a href="{{ route('sessions.index') }}">{{ __('Find training dates') }} <span>↗</span></a>
                            <a href="{{ route('opportunities.index') }}">{{ __('See jobs') }} <span>↗</span></a>
                    @endswitch
                </div>
            </section>
            <section class="workspace-stats" aria-label="{{ __('Your overview') }}">
                @foreach ($stats as $stat)
                    <article><div><span>{{ $stat['label'] }}</span><span class="workspace-stat-number">0{{ $loop->iteration }}</span></div><strong>{{ $stat['value'] }}</strong><p>{{ $stat['note'] }} @if (! empty($stat['delta']))<em>{{ $stat['delta'] }}</em>@endif</p></article>
                @endforeach
            </section>
            <section class="workspace-focus">
                <div class="workspace-focus-icon"><svg class="ic" aria-hidden="true"><use href="#{{ $isYouth ? 'i-scan' : 'i-cap' }}"/></svg></div>
                <div><span class="workspace-eyebrow">{{ $isYouth ? 'BUILD YOUR NEXT STEP' : 'CONNECT PEOPLE TO OPPORTUNITY' }}</span><h2>{{ $isYouth ? 'Start with what interests you.' : 'Make room for new possibilities.' }}</h2><p>{{ $isYouth ? 'Save a short skills and interests assessment to see initial job, career, and training suggestions. No resume or NC certificate is required; scanning is optional.' : 'Explore qualifications and help your community find a training path.' }}</p></div>
                <a class="workspace-primary" href="{{ $isYouth ? route('youth.assessment.index') : route('tesda.index') }}">{{ $isYouth ? 'Take assessment' : 'Explore training' }} <span aria-hidden="true">↗</span></a>
            </section>
            <nav class="workspace-sections" aria-label="{{ __('Dashboard sections') }}">
                <a href="#progress">{{ __('Progress') }}</a><a href="#next-steps">{{ __('Next steps') }}</a>
                @if ($isYouth)<a href="#career-insights">{{ __('Career insights') }}</a>@endif
                <a href="#recent-activity">{{ __('Activity') }}</a><a href="#training-library">{{ __('Training') }}</a><a href="#workspace-assistant">Ask Tuklas ↗</a>
            </nav>
            <div class="workspace-columns">
                <div class="workspace-content">
                    <section class="workspace-panel workspace-progress" id="progress">@include('dashboard.partials.chart')</section>
                    <section class="workspace-panel tbl" id="recent-activity">@include('dashboard.partials.table')</section>
                    @if ($isYouth)<div id="career-insights">@include('dashboard.partials.insights')</div>@endif
                    <section class="workspace-library workspace-panel" id="training-library">
                        <header class="workspace-section-heading"><div><p class="workspace-eyebrow">{{ __('MORE WAYS FORWARD') }}</p><h2>{{ __('Find your training path.') }}</h2></div><a href="{{ route('tesda.index') }}#nc-offerings">View all ↗</a></header>
                        <p class="workspace-muted">{{ $currentQualifications->count() }} national qualifications across NC I–IV. Explore the official standards, then find a registered provider.</p>
                        <div class="workspace-levels">
                            @foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)
                                <a href="{{ route('tesda.index', ['level' => $level]) }}#nc-offerings"><span>{{ $level }}</span><strong>{{ $currentQualifications->where('nc_level', $level)->count() }}</strong><small>{{ __('qualifications') }} <b>↗</b></small></a>
                            @endforeach
                        </div>
                        <p class="workspace-footnote">{{ __('National catalog · Local schedules, slots, and entry requirements depend on the provider.') }}</p>
                    </section>
                </div>
                <aside class="workspace-side">
                    <section class="workspace-panel tasks" id="next-steps">@include('dashboard.partials.tasks', ['tasks' => $journey])</section>
                    <section class="workspace-panel workspace-shortcuts"><p class="workspace-eyebrow">{{ __('QUICK ACCESS') }}</p><h2>{{ __('Keep moving.') }}</h2><div>@foreach ($actions as $action)@if ($action['href'])<a href="{{ $action['href'] }}"><svg class="ic" aria-hidden="true"><use href="#i-{{ $action['icon'] }}"/></svg>{{ $action['label'] }}<span>↗</span></a>@endif @endforeach</div></section>
                    <div id="workspace-assistant"><x-career-chat /></div>
                </aside>
            </div>
            <footer class="workspace-footer"><span>Tuklas / your next possibility</span><a href="{{ route('tesda.index') }}">Official TESDA resources ↗</a></footer>
        </div>
    </div>
</x-app-layout>
