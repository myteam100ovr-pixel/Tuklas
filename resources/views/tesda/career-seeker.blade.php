<section class="tesda-section tesda-assessment-matches" aria-labelledby="career-seeker-training-matches-title">
    <div class="tesda-section-heading">
        <div>
            <p class="tesda-eyebrow">Based on your skills assessment</p>
            <h2 id="career-seeker-training-matches-title">{{ __('Training recommendations for you') }}</h2>
        </div>
        <a class="tesda-provider-directory__link" href="{{ route('youth.assessment.index') }}">Update your assessment ↗</a>
    </div>
    <p class="tesda-note">These matches compare your saved interests and self-reported skills with published courses. They are suggestions; the center confirms enrollment dates and places.</p>
    @if ($careerSeekerTrainingRecommendations !== [])
        <div class="tesda-assessment-match-grid">
            @foreach ($careerSeekerTrainingRecommendations as $recommendation)
                <article class="tesda-assessment-match">
                    <h3>{{ $recommendation['title'] }}</h3>
                    <p>{{ $recommendation['detail'] }}</p>
                    <a href="#career-seeker-ptc-psat">View course listings ↓</a>
                </article>
            @endforeach
        </div>
    @else
        <div class="tesda-empty">
            <p>No published course closely matches your assessment yet. Browse all available course listings below.</p>
        </div>
    @endif
</section>

<section class="tesda-section" id="career-seeker-ptc-psat" aria-labelledby="career-seeker-ptc-psat-title">
    <div class="tesda-section-heading">
        <div>
            <p class="tesda-eyebrow">Training centers · Pangasinan</p>
            <h2 id="career-seeker-ptc-psat-title">Courses by training center</h2>
        </div>
        <span class="tesda-count">{{ $careerSeekerPrograms->count() }} listings</span>
    </div>
    <p class="tesda-note">Filter by PTC, PSAT, or private training center. Apply for a published course; if no intake is scheduled, the center will confirm dates and available places before accepting. PESO and TESDA manage each session’s place limit.</p>

    <form class="tesda-center-filter" method="GET" action="{{ route('tesda.index') }}">
        <label for="training-center-type">{{ __('Training center type') }}</label>
        <select id="training-center-type" name="center_type">
            <option value="" @selected($careerSeekerCenterType === null)>{{ __('All training centers') }}</option>
            <option value="PTC" @selected($careerSeekerCenterType === 'PTC')>PTC · Provincial Training Center</option>
            <option value="PSAT" @selected($careerSeekerCenterType === 'PSAT')>PSAT · Pangasinan School of Arts and Trades</option>
            <option value="PRIVATE" @selected($careerSeekerCenterType === 'PRIVATE')>{{ __('Private training centers') }}</option>
        </select>
        <button type="submit">Apply filter</button>
        @if ($careerSeekerCenterType !== null)
            <a href="{{ route('tesda.index') }}">{{ __('Clear') }}</a>
        @endif
    </form>

    @if ($careerSeekerProgramGroups->isEmpty())
        <div class="tesda-empty">
            <h3>{{ __('No published courses are available for this filter yet.') }}</h3>
            <p>Try another center type or contact a listed provider to confirm its current courses and schedules.</p>
        </div>
    @else
        <div class="tesda-provider-grid tesda-provider-grid--public-courses">
            @foreach ($careerSeekerProgramGroups as $schoolPrograms)
                @php($center = $schoolPrograms->first()->center)
                <article class="tesda-provider-card tesda-provider-card--course-list">
                    <header>
                        <div>
                            <h3>{{ $center->name }}</h3>
                            <span class="tesda-center-type">{{ ['PTC' => 'PTC · Provincial Training Center', 'PSAT' => 'PSAT · Pangasinan School of Arts and Trades', 'PRIVATE' => 'Private training center'][$center->type] ?? $center->type }}</span>
                            @if ($center->address)
                                <p>{{ $center->address }}</p>
                            @endif
                        </div>
                        <span>{{ $schoolPrograms->count() }} {{ \Illuminate\Support\Str::plural('course', $schoolPrograms->count()) }}</span>
                    </header>
                    <ul class="tesda-course-list">
                        @foreach ($schoolPrograms as $program)
                            <li class="tesda-course-choice">
                                <div class="tesda-course-overview">
                                    <strong>{{ $program->title }}</strong>
                                    <span>{{ $program->duration_days ? $program->duration_days.' training days' : ($program->duration_hours ? $program->duration_hours.' training hours' : 'Training duration not listed in the schedule') }}</span>
                                </div>
                                @php($openSessions = $program->sessions)
                                @php($courseApplication = $program->applications->first())
                                @forelse ($openSessions as $session)
                                    @php($application = $courseApplication ?? $session->applications->first())
                                    @php($placesLeft = max(0, $session->capacity - $session->accepted_count))
                                    <div class="tesda-course-session">
                                        <div class="tesda-course-session__details">
                                            <time datetime="{{ $session->starts_at->toIso8601String() }}">{{ $session->starts_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</time>
                                            <span class="{{ $placesLeft === 0 ? 'is-full' : '' }}">{{ $placesLeft }} of {{ $session->capacity }} places available</span>
                                        </div>
                                        @if ($application && in_array($application->status, ['pending', 'accepted'], true))
                                            <span class="tesda-session-status">{{ ucfirst($application->status) }}</span>
                                        @elseif ($placesLeft === 0)
                                            <span class="tesda-session-status is-full">Fully occupied</span>
                                        @else
                                            <form method="POST" action="{{ route('applications.store', $session) }}">
                                                @csrf
                                                <button type="submit">Request course</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <div class="tesda-course-session tesda-course-session--empty">
                                        @if ($courseApplication && in_array($courseApplication->status, ['pending', 'accepted'], true))
                                            <span>Your application is {{ $courseApplication->status === 'accepted' ? 'approved' : 'pending' }}. The center will confirm the intake details.</span>
                                        @else
                                            <div class="tesda-course-session__details">
                                                <strong>No intake date is listed yet.</strong>
                                                <span>Apply now; the center must confirm dates and available places before accepting enrollment.</span>
                                            </div>
                                            <form method="POST" action="{{ route('applications.program-store', $program) }}">
                                                @csrf
                                                <button type="submit">{{ __('Apply for this course') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                @endforelse
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    @endif
</section>

@if ($careerSeekerCenterType === null || $careerSeekerCenterType === 'PRIVATE')
<section class="tesda-section tesda-provider-directory" id="career-seeker-other-providers" aria-labelledby="career-seeker-other-providers-title">
    <div class="tesda-section-heading">
        <div>
            <p class="tesda-eyebrow">If no public-school intake is open</p>
            <h2 id="career-seeker-other-providers-title">{{ __('Private TESDA-registered training centers') }}</h2>
        </div>
        <a class="tesda-provider-directory__link" href="https://www.tesda.gov.ph/Tvi/Result?SearchCourse=&SearchIns=&SearchLoc=pangasinan" target="_blank" rel="noopener noreferrer">Open official TESDA directory ↗</a>
    </div>
    <p class="tesda-note">Browse other registered schools, including Edson Technical Vocational Training and Assessment Center and Ledesma School of Skills Development. The registry lists provider qualifications, not current enrollment, schedules, or training duration; contact the school before applying.</p>
    <div class="tesda-provider-tools">
        <label for="tesda-provider-search">Search schools, locations, or courses</label>
        <input id="tesda-provider-search" type="search" data-provider-search placeholder="Try Edson, Ledesma, a town, or a course" aria-controls="tesda-provider-results">
        <p data-provider-count role="status">{{ $careerSeekerProviderCount }} provider locations</p>
    </div>
    <div class="tesda-provider-grid" id="tesda-provider-results" data-provider-directory>
        @foreach ($careerSeekerProviders as $provider)
            <article class="tesda-provider-card" data-provider-card>
                <header><div><h3>{{ $provider['provider'] }}</h3><p>{{ $provider['address'] }}</p></div><span>{{ $provider['programs']->count() }} {{ \Illuminate\Support\Str::plural('course', $provider['programs']->count()) }}</span></header>
                @if ($provider['phones']->isNotEmpty())<p class="tesda-provider-phone">{{ $provider['phones']->implode(' · ') }}</p>@endif
                <details><summary>View registered courses</summary><ul>@foreach ($provider['programs'] as $program)<li>{{ $program }} · Training duration not listed in directory</li>@endforeach</ul></details>
            </article>
        @endforeach
    </div>
    <p class="tesda-note" data-provider-empty hidden>No registered provider entries match that search.</p>
    <p class="tesda-provider-source">{{ __('Source:') }} <a href="https://www.tesda.gov.ph/Tvi/Result?SearchCourse=&SearchIns=&SearchLoc=pangasinan" target="_blank" rel="noopener noreferrer">{{ __('TESDA TVI Pangasinan registry') }}</a> · Snapshot fetched {{ $registeredProgramsFetchedAt ? \Carbon\Carbon::parse($registeredProgramsFetchedAt)->timezone('Asia/Manila')->format('M j, Y') : 'date unavailable' }}.</p>
</section>
@endif
