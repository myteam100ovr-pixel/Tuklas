<section class="tesda-section" id="career-seeker-ptc-psat" aria-labelledby="career-seeker-ptc-psat-title">
    <div class="tesda-section-heading">
        <div>
            <p class="tesda-eyebrow">Training centers · Pangasinan</p>
            <h2 id="career-seeker-ptc-psat-title">Courses by training center</h2>
        </div>
        <span class="tesda-count">{{ $careerSeekerPrograms->count() }} listings</span>
    </div>
    <p class="tesda-note">Filter by PTC, PSAT, or private training center. Choose a course with an upcoming schedule to request a place. PESO and TESDA manage each session’s place limit; pending requests still need the school’s review.</p>

    <form class="tesda-center-filter" method="GET" action="{{ route('tesda.index') }}">
        <label for="training-center-type">Training center type</label>
        <select id="training-center-type" name="center_type">
            <option value="" @selected($careerSeekerCenterType === null)>All training centers</option>
            <option value="PTC" @selected($careerSeekerCenterType === 'PTC')>PTC · Provincial Training Center</option>
            <option value="PSAT" @selected($careerSeekerCenterType === 'PSAT')>PSAT · Pangasinan School of Arts and Trades</option>
            <option value="PRIVATE" @selected($careerSeekerCenterType === 'PRIVATE')>Private training centers</option>
        </select>
        <button type="submit">Apply filter</button>
        @if ($careerSeekerCenterType !== null)
            <a href="{{ route('tesda.index') }}">Clear</a>
        @endif
    </form>

    @if ($careerSeekerProgramGroups->isEmpty())
        <div class="tesda-empty">
            <h3>No published courses are available for this filter yet.</h3>
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
                                @forelse ($openSessions as $session)
                                    @php($application = $session->applications->first())
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
                                                <button type="submit">Request a place</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <div class="tesda-course-session tesda-course-session--empty">No upcoming intake is listed by this center.</div>
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
            <h2 id="career-seeker-other-providers-title">Private TESDA-registered training centers</h2>
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
    <p class="tesda-provider-source">Source: <a href="https://www.tesda.gov.ph/Tvi/Result?SearchCourse=&SearchIns=&SearchLoc=pangasinan" target="_blank" rel="noopener noreferrer">TESDA TVI Pangasinan registry</a> · Snapshot fetched {{ $registeredProgramsFetchedAt ? \Carbon\Carbon::parse($registeredProgramsFetchedAt)->timezone('Asia/Manila')->format('M j, Y') : 'date unavailable' }}.</p>
</section>
@endif
