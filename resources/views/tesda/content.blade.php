<main class="tesda-main">
    <section class="tesda-intro" aria-labelledby="tesda-title">
        <p class="tesda-eyebrow">TESDA Lingayen · Pangasinan</p>
        <h1 id="tesda-title">Explore training and services.</h1>
        <p>Browse programs published by TESDA Lingayen in Tuklas, or search TESDA’s live registered-provider directory for National Certificates I to IV across Pangasinan.</p>
        <a class="btn btn-violet" href="#nc-offerings">Browse NC I–IV offerings</a>
    </section>

    <section class="tesda-section" id="nc-offerings" aria-labelledby="nc-offerings-title">
        <div class="tesda-section-heading">
            <div>
                <p class="tesda-eyebrow">Live TESDA provider directory · Pangasinan</p>
                <h2 id="nc-offerings-title">Find registered programs by NC level</h2>
            </div>
        </div>
        <p class="tesda-note">These links search TESDA’s registered training-provider directory by certificate level. Results depend on TESDA’s current registrations; contact the provider to confirm schedules, slots, fees, and eligibility.</p>
        <div class="tesda-resource-grid">
            @foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)
                <article class="tesda-resource">
                    <span class="tesda-resource-number">{{ $level }}</span>
                    <h3>{{ $level }} registered programs</h3>
                    <p>Search TESDA’s current Pangasinan provider listings for {{ $level }} programs.</p>
                    <a href="https://www.tesda.gov.ph/Tvi/Result?SearchCourse={{ urlencode($level) }}&amp;SearchLoc=pangasinan" target="_blank" rel="noopener">View TESDA results <span aria-hidden="true">↗</span></a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="tesda-section" id="local-programs" aria-labelledby="local-programs-title">
        <div class="tesda-section-heading">
            <div>
                <p class="tesda-eyebrow">Tuklas local catalog</p>
                <h2 id="local-programs-title">TESDA Lingayen programs</h2>
            </div>
            <span class="tesda-count">{{ $programCount }} published</span>
        </div>
        <p class="tesda-note">This list includes programs published in Tuklas by authorized local staff. Training dates, slots, requirements, and scholarship availability can change; confirm them with TESDA or the provider before applying.</p>

        @if ($programCount === 0)
            <div class="tesda-empty">
                <h3>No local programs have been published yet.</h3>
                <p>Use the NC I–IV searches above to find current Pangasinan provider listings on TESDA’s website.</p>
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
                                        <h4>{{ $program->title }}</h4>
                                        @if ($program->description)
                                            <p>{{ $program->description }}</p>
                                        @endif
                                    </div>
                                    <div class="tesda-offer-meta">
                                        @if ($program->duration_hours)<span>{{ $program->duration_hours }} training hours</span>@endif
                                        @if ($program->schedule_note)<span>{{ $program->schedule_note }}</span>@endif
                                        @if ($program->last_verified_at)<small>Checked {{ $program->last_verified_at->format('M Y') }}</small>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </section>

    <section class="tesda-section" aria-labelledby="tesda-services-title">
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
</main>

<footer class="tesda-footer">
    <a href="{{ route('home') }}">← Back to Tuklas</a>
    <span>Live provider searches and official resources open on TESDA websites. Availability and requirements are set by TESDA and training providers.</span>
</footer>
