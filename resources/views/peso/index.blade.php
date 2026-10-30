<x-app-layout>
    <div class="suite-page suite-peso">
    <x-workspace-heading eyebrow="PESO · CAREER EXPLORATION" title="See where your skills could lead" :description="$isPESOAdmin ? 'Explore career suggestions grounded in youth resumes and certificates.' : 'Connect your experience to possible roles. Understand the evidence behind each suggestion and choose a practical next step.'" />
    <div class="suite-split">
    <section class="card peso-matches" aria-labelledby="peso-matches-title">
        <div class="peso-matches__header">
            <div><span class="workspace-eyebrow">FROM SCANNED EVIDENCE</span><h2 id="peso-matches-title">{{ $isPESOAdmin ? 'Youth career matches' : 'Your career matches' }}</h2><p>Review the role, the documented skills, and what to build next.</p></div>
            <span class="peso-matches__count">{{ number_format($matchingScanCount) }} {{ $matchingScanCount === 1 ? 'scan' : 'scans' }} with matches</span>
        </div>

        <p class="peso-matches__notice">
            These suggestions are career guidance based on scanned documents, not hiring decisions or confirmed vacancies. A percentage shows documented skills out of the role skills listed by the scanner, not a hiring probability.
        </p>

        @if ($recommendationPages->isNotEmpty())
            <div class="recommendation-list">
                @foreach ($recommendationGroups as $group)
                    @php
                        $userScans = $group['scans'];
                    @endphp
                    <details class="recommendation-person">
                        <summary class="recommendation-person__summary">
                            <span class="recommendation-person__identity">
                                <span class="workspace-eyebrow">{{ $isPESOAdmin ? 'YOUTH ACCOUNT' : 'YOUR SCAN HISTORY' }}</span>
                                <strong>{{ $isPESOAdmin ? $group['name'] : 'Your career matches' }}</strong>
                            </span>
                            <span class="recommendation-person__overview">
                                <span>{{ $userScans->count() }} {{ $userScans->count() === 1 ? 'scan' : 'scans' }}{{ $isPESOAdmin ? '' : ' on this page' }}</span>
                                <span>{{ $isPESOAdmin ? 'Latest scan' : 'Latest on this page' }}: {{ $userScans->first()->processed_at?->format('M j, Y') ?? 'Date unavailable' }}</span>
                                <span class="recommendation-disclosure" aria-hidden="true">⌄</span>
                            </span>
                        </summary>
                        <div class="recommendation-person__scans">
                @foreach ($userScans as $scan)
                    @php
                        $jobRecommendations = collect($scan->result['jobRecommendations'] ?? [])
                            ->filter(fn ($recommendation) => is_array($recommendation))
                            ->values();
                        $documentType = match ($scan->doc_type) {
                            'resume' => 'Resume',
                            'certificate' => 'Certificate',
                            default => 'Uploaded document',
                        };
                    @endphp

                    <details class="recommendation-card">
                        <summary class="recommendation-card__meta">
                            <span class="recommendation-card__scan-label"><strong>{{ $documentType }}</strong><span>Scanned {{ $scan->processed_at?->format('M j, Y · g:i A') ?? 'date unavailable' }}</span><small title="{{ $scan->original_name }}">{{ $scan->original_name }}</small></span>
                            <span class="recommendation-card__count">{{ $jobRecommendations->count() }} {{ $jobRecommendations->count() === 1 ? 'path' : 'paths' }} <span class="recommendation-disclosure" aria-hidden="true">⌄</span></span>
                        </summary>

                        <div class="recommendation-card__jobs">
                            @foreach ($jobRecommendations as $recommendation)
                                <section class="recommendation-card__job">
                                    <div class="recommendation-card__job-head">
                                        <span class="recommendation-card__rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <h3>{{ is_string($recommendation['title'] ?? null) && filled($recommendation['title']) ? $recommendation['title'] : 'Recommended career path' }}</h3>
                                        @if (is_array($recommendation['skillCoverage'] ?? null))
                                            <span class="recommendation-card__coverage"><strong>{{ $recommendation['skillCoverage']['percent'] }}%</strong> documented skill coverage</span>
                                        @endif
                                    </div>
                                    @if (is_array($recommendation['skillCoverage'] ?? null))
                                        <p class="recommendation-card__coverage-detail">{{ $recommendation['skillCoverage']['matched'] }} of {{ $recommendation['skillCoverage']['total'] }} listed role skills documented in this scan</p>
                                    @else
                                        <p class="recommendation-card__coverage-detail">Rescan to calculate documented skill coverage.</p>
                                    @endif
                                    @if (is_string($recommendation['reason'] ?? null) && filled($recommendation['reason']))
                                        <p class="recommendation-card__reason">{{ $recommendation['reason'] }}</p>
                                    @endif
                                    @if (! empty($recommendation['evidencedSkills']) || ! empty($recommendation['skillsToDevelop']))
                                        <div class="recommendation-card__skills">
                                            @if (is_array($recommendation['evidencedSkills'] ?? null) && $recommendation['evidencedSkills'] !== [])
                                                <div><h4>Documented skills</h4><ul>@foreach ($recommendation['evidencedSkills'] as $skill)<li>{{ $skill }}</li>@endforeach</ul></div>
                                            @endif
                                            @if (is_array($recommendation['skillsToDevelop'] ?? null) && $recommendation['skillsToDevelop'] !== [])
                                                <div><h4>Build or document next</h4><ul>@foreach ($recommendation['skillsToDevelop'] as $skill)<li>{{ $skill }}</li>@endforeach</ul></div>
                                            @endif
                                        </div>
                                    @endif
                                    @if (is_string($recommendation['evidence'] ?? null) && filled($recommendation['evidence']))
                                        <p class="recommendation-card__evidence"><strong>Scan evidence</strong> {{ $recommendation['evidence'] }}</p>
                                    @elseif (is_array($recommendation['evidence'] ?? null))
                                        <ul class="suite-evidence">@foreach ($recommendation['evidence'] as $evidence)@if (is_string($evidence))<li>{{ $evidence }}</li>@endif @endforeach</ul>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </details>
                @endforeach
                        </div>
                    </details>
                @endforeach
            </div>

            @if ($recommendationPages->hasPages())
                <nav class="users-directory__pagination" aria-label="PESO job match pages">
                    @if ($recommendationPages->previousPageUrl())
                        <a href="{{ $recommendationPages->previousPageUrl() }}" rel="prev">Previous</a>
                    @else
                        <span aria-disabled="true">Previous</span>
                    @endif

                    <span>Page {{ $recommendationPages->currentPage() }} of {{ $recommendationPages->lastPage() }}</span>

                    @if ($recommendationPages->nextPageUrl())
                        <a href="{{ $recommendationPages->nextPageUrl() }}" rel="next">Next</a>
                    @else
                        <span aria-disabled="true">Next</span>
                    @endif
                </nav>
            @endif
        @else
            <div class="empty peso-matches__empty">
                <svg class="ic" aria-hidden="true"><use href="#i-briefcase"/></svg>
                <p>{{ $isPESOAdmin ? 'No completed youth scans with job matches yet.' : 'No job matches yet. Use the AI Scanner to analyze a resume or certificate first.' }}</p>
                @unless ($isPESOAdmin)
                    <a class="btn" href="{{ route('scanner.index') }}">Open AI Scanner</a>
                @endunless
            </div>
        @endif
    </section>
    <aside class="suite-aside"><section class="suite-note peso-next-steps"><span class="workspace-eyebrow">FROM MATCH TO ACTION</span><h2>Make your next move.</h2><ol><li>Compare the role’s requirements with your documented skills.</li><li>Choose one skill to build or document next.</li><li>Confirm real openings with PESO or the employer.</li></ol><a href="{{ route('tesda.index') }}">Explore TESDA training ↗</a><a href="{{ route('scanner.index') }}">Add new document evidence ↗</a></section><x-career-chat /></aside>
    </div>
    </div>
</x-app-layout>
