<section class="tesda-section" id="career-seeker-ptc-psat" aria-labelledby="career-seeker-ptc-psat-title">
    <div class="tesda-section-heading">
        <div>
            <p class="tesda-eyebrow">Public training centers · Pangasinan</p>
            <h2 id="career-seeker-ptc-psat-title">Courses listed by PTC and PSAT</h2>
        </div>
        <span class="tesda-count">{{ $careerSeekerPrograms->count() }} listings</span>
    </div>
    <p class="tesda-note">Course titles and training days below come from the schedule provided for these schools. A listing does not confirm an open intake or available seats. Contact the school to confirm current dates and enrollment.</p>

    @if ($careerSeekerProgramGroups->isEmpty())
        <div class="tesda-empty">
            <h3>No PTC or PSAT course listings are currently published.</h3>
            <p>Check the other registered providers below and contact them to confirm current course availability.</p>
        </div>
    @else
        <div class="tesda-provider-grid">
            @foreach ($careerSeekerProgramGroups as $schoolName => $schoolPrograms)
                <article class="tesda-provider-card">
                    <header>
                        <div>
                            <h3>{{ $schoolName }}</h3>
                            @if ($schoolPrograms->first()->center->address)
                                <p>{{ $schoolPrograms->first()->center->address }}</p>
                            @endif
                        </div>
                        <span>{{ $schoolPrograms->count() }} {{ \Illuminate\Support\Str::plural('course', $schoolPrograms->count()) }}</span>
                    </header>
                    <ul>
                        @foreach ($schoolPrograms as $program)
                            <li><strong>{{ $program->title }}</strong><br>{{ $program->duration_days ? $program->duration_days.' training days' : ($program->duration_hours ? $program->duration_hours.' training hours' : 'Training duration not listed in the schedule') }}</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="tesda-section tesda-provider-directory" id="career-seeker-other-providers" aria-labelledby="career-seeker-other-providers-title">
    <div class="tesda-section-heading">
        <div>
            <p class="tesda-eyebrow">If no public-school intake is open</p>
            <h2 id="career-seeker-other-providers-title">Other TESDA-registered providers</h2>
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
