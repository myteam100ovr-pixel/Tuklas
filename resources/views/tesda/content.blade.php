<div class="tesda-main suite-tesda tesda-directory">
    <section class="tesda-intro" aria-labelledby="tesda-title">
        <p class="tesda-eyebrow">LEARN. QUALIFY. MOVE FORWARD.</p>
        <h1 id="tesda-title">Build skills.<br>Open new possibilities.</h1>
        <p>Explore the official NC I–IV qualification catalog, find registered providers, and browse locally published training programs.</p>
        <div class="tesda-directory-actions"><a class="btn btn-violet" href="#nc-offerings">Find your qualification <span aria-hidden="true">↗</span></a><a href="#local-programs">Explore local training →</a></div>
        <div class="tesda-directory-summary"><span><strong>NC I–IV</strong>National qualifications</span><span><strong>{{ $programCount }}</strong>Published local programs</span><span><strong>Your next step</strong>One training plan at a time</span></div>
    </section>

    <nav class="tesda-directory-nav" aria-label="Training directory sections"><a href="#nc-offerings"><span>01</span> Qualifications</a><a href="#provider-directory"><span>02</span> Pangasinan providers</a><a href="#local-programs"><span>03</span> Local training</a><a href="#tesda-resources"><span>04</span> Learning resources</a></nav>
    @include('tesda.qualifications')

    <section class="tesda-section tesda-provider-directory" id="provider-directory" aria-labelledby="provider-directory-title">
        <div class="tesda-section-heading">
            <div>
                <p class="tesda-eyebrow">Official TESDA registry · Pangasinan</p>
                <h2 id="provider-directory-title">Training centers and registered programs</h2>
            </div>
            <a class="tesda-provider-directory__link" href="https://www.tesda.gov.ph/Tvi/Result?SearchCourse=&SearchIns=&SearchLoc=pangasinan" target="_blank" rel="noopener noreferrer">Open official TESDA directory ↗</a>
        </div>
        <p class="tesda-note">{{ $registeredProgramCount }} program registrations across {{ $providerCount }} provider locations from the official TESDA Pangasinan registry. Listings are not confirmation of open enrollment; check schedules and slots with the provider.</p>
        <div class="tesda-provider-tools">
            <label for="tesda-provider-search">Search providers, addresses, or registered programs</label>
            <input id="tesda-provider-search" type="search" data-provider-search placeholder="Try a town, training center, or qualification" aria-controls="tesda-provider-results">
            <p data-provider-count role="status">{{ $providerCount }} provider locations</p>
        </div>
        <div class="tesda-provider-grid" id="tesda-provider-results" data-provider-directory>
            @foreach ($providerGroups as $provider)
                <article class="tesda-provider-card" data-provider-card>
                    <header><div><h3>{{ $provider['provider'] }}</h3><p>{{ $provider['address'] }}</p></div><span>{{ $provider['programs']->count() }} {{ \Illuminate\Support\Str::plural('program', $provider['programs']->count()) }}</span></header>
                    @if ($provider['phones']->isNotEmpty())<p class="tesda-provider-phone">{{ $provider['phones']->implode(' · ') }}</p>@endif
                    <details><summary>View registered programs</summary><ul>@foreach ($provider['programs'] as $program)<li>{{ $program }}</li>@endforeach</ul></details>
                </article>
            @endforeach
        </div>
        <p class="tesda-note" data-provider-empty hidden>No official registry entries match that search.</p>
        <p class="tesda-provider-source">Source: <a href="https://www.tesda.gov.ph/Tvi/Result?SearchCourse=&SearchIns=&SearchLoc=pangasinan" target="_blank" rel="noopener noreferrer">TESDA TVI Pangasinan registry</a> · Snapshot fetched {{ $registeredProgramsFetchedAt ? \Carbon\Carbon::parse($registeredProgramsFetchedAt)->timezone('Asia/Manila')->format('M j, Y') : 'date unavailable' }}.</p>
    </section>

    <section class="tesda-section" id="local-programs" aria-labelledby="local-programs-title">
        <div class="tesda-section-heading">
            <div>
                <p class="tesda-eyebrow">Tuklas local catalog</p>
                <h2 id="local-programs-title">TESDA programs</h2>
            </div>
            <span class="tesda-count">{{ $programCount }} published</span>
        </div>
        <p class="tesda-note">This list includes programs published in Tuklas by authorized local staff. Training dates, slots, requirements, and scholarship availability can change; confirm them with TESDA or the provider before applying.</p>
        @auth
            @if (auth()->user()->hasRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::TesdaAdmin))
                <aside class="tesda-note" aria-label="Training catalog review process">
                    <strong>For TESDA staff:</strong> Review source-listed programs, confirm requirements and schedules with the provider, then publish verified details.
                    <a class="tesda-manage-link" href="{{ route('admin.trainings.index') }}">Review training program drafts ↗</a>
                </aside>
            @endif
            @if (auth()->user()->hasRole(\App\Enums\Role::Trainer))
                <a class="tesda-manage-link" href="{{ route('trainer.programs.index') }}">My teaching courses ↗</a>
            @endif
            @if (session('status'))<p class="qualification-feedback" role="status">{{ session('status') }}</p>@endif
        @endauth

        @if ($programCount === 0)
            <div class="tesda-empty">
                <h3>No local programs have been published yet.</h3>
                <p>Explore the qualifications above and use “Find a provider” to check TESDA’s registered schools, including providers in Pangasinan.</p>
            </div>
        @else
            <div class="tesda-level-list">
                @foreach ($programGroups as $level => $levelPrograms)
                    <section class="tesda-level-group" aria-labelledby="tesda-level-{{ $loop->index }}">
                        <header class="tesda-level-header">
                            <span class="tesda-level-mark" aria-hidden="true">{{ $loop->iteration }}</span>
                            <div class="tesda-level-heading">
                                <p>National Certificate</p>
                                <h3 id="tesda-level-{{ $loop->index }}">{{ $level }}</h3>
                            </div>
                            <span class="tesda-level-count">{{ $levelPrograms->count() }} {{ $levelPrograms->count() === 1 ? 'program' : 'programs' }}</span>
                        </header>
                        <ul class="tesda-offers">
                            @foreach ($levelPrograms as $program)
                                <li class="tesda-offer">
                                    <div class="tesda-offer-main">
                                        <h4>{{ $program->title }} @if ($program->is_demo)<small class="tesda-application-state">Simulated demo data</small>@endif</h4>
                                        @if ($program->description)
                                            <p>{{ $program->description }}</p>
                                        @endif
                                    </div>
                                    <div class="tesda-offer-meta">
                                        <span>{{ $program->duration_days ? $program->duration_days.' training days' : ($program->duration_hours ? $program->duration_hours.' training hours' : 'Training duration not yet announced') }}</span>
                                        @if ($program->center)<span>Center: {{ $program->center->name }}</span>@endif
                                        <span>{{ $program->schedule_note ?: 'Schedule not yet announced' }}</span>
                                        <span>Trainer: {{ $program->teachingAssignments->pluck('trainer.name')->filter()->unique()->implode(', ') ?: 'Not yet assigned' }}</span>
                                        @if ($program->last_verified_at)<small>Checked {{ $program->last_verified_at->format('M Y') }}</small>@endif
                                        @auth
                                            @if (auth()->user()->hasRole(\App\Enums\Role::Trainer))
                                                @if ($teachingByProgram->has($program->id))
                                                    <span class="tesda-application-state">On your teaching list</span>
                                                    <form method="POST" action="{{ route('trainer.teaching.destroy', $program) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="tesda-application-button is-secondary" type="submit">Stop teaching</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('trainer.teaching.store', $program) }}">
                                                        @csrf
                                                        <button class="tesda-application-button" type="submit">Choose to teach</button>
                                                    </form>
                                                @endif
                                            @endif
                                        @endauth
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </section>

    <section class="tesda-section" id="tesda-resources" aria-labelledby="tesda-services-title">
        <div class="tesda-section-heading">
            <div>
                <p class="tesda-eyebrow">Official TESDA resources</p>
                <h2 id="tesda-services-title">More ways to learn and get certified</h2>
            </div>
        </div>
        <div class="tesda-resource-grid">
            <article class="tesda-resource">
                <span class="tesda-resource-number">01</span>
                <h3>Online learning</h3>
                <p>Explore courses across sectors through the TESDA Online Program.</p>
                <a href="https://e-tesda.gov.ph/course/" target="_blank" rel="noopener">Browse online courses <span aria-hidden="true">↗</span></a>
            </article>
            <article class="tesda-resource">
                <span class="tesda-resource-number">02</span>
                <h3>Scholarships and student assistance</h3>
                <p>Read TESDA’s official information about scholarship programs and application channels.</p>
                <a href="https://tesda.gov.ph/About/TESDA/1279" target="_blank" rel="noopener">Explore assistance programs <span aria-hidden="true">↗</span></a>
            </article>
            <article class="tesda-resource">
                <span class="tesda-resource-number">03</span>
                <h3>Assessment and certification</h3>
                <p>Learn how competency assessment works and where to find accredited assessment centers.</p>
                <a href="https://tesda.gov.ph/about/tesda/25" target="_blank" rel="noopener">Read about certification <span aria-hidden="true">↗</span></a>
            </article>
            <article class="tesda-resource">
                <span class="tesda-resource-number">04</span>
                <h3>National qualifications and training regulations</h3>
                <p>Search TESDA’s official national qualification standards and training regulations.</p>
                <a href="https://tesda.gov.ph/Download/Training_Regulations" target="_blank" rel="noopener">View training regulations <span aria-hidden="true">↗</span></a>
            </article>
        </div>
    </section>
</div>

<footer class="tesda-footer">
    <a href="{{ route('home') }}">← Back to Tuklas</a>
    <span>Live provider searches and official resources open on TESDA websites. Availability and requirements are set by TESDA and training providers.</span>
</footer>
