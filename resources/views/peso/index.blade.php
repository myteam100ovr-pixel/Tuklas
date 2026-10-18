<x-app-layout>
    <div class="suite-page suite-peso">
    <x-workspace-heading eyebrow="PESO · CAREER EXPLORATION" title="See where your skills could lead" :description="$isPESOAdmin ? 'Explore career suggestions grounded in youth resumes and certificates.' : 'Connect your experience to possible roles. Understand the evidence behind each suggestion and choose a practical next step.'" />
    <div class="suite-split">
    <section class="card peso-matches" aria-labelledby="peso-matches-title">
        <div class="card-head">
            <h2 id="peso-matches-title">{{ $isPESOAdmin ? 'Youth job matches' : 'Your job matches' }}</h2>
            <span class="users-directory__count">{{ number_format($recommendationScans->total()) }} completed scans</span>
        </div>

        <p class="peso-matches__notice">
            These suggestions are career guidance based on the scanned documents, not hiring decisions or confirmed vacancies.
        </p>

        @if ($recommendationScans->isNotEmpty())
            <div class="recommendation-list">
                @foreach ($recommendationScans as $scan)
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

                    <article class="recommendation-card">
                        <div class="recommendation-card__meta">
                            @if ($isPESOAdmin)
                                <strong>{{ $scan->user->name }}</strong>
                            @else
                                <strong>{{ $scan->original_name }}</strong>
                            @endif
                            <span>{{ $documentType }} · {{ $scan->processed_at?->format('M j, Y') ?? 'Date unavailable' }}</span>
                        </div>

                        <div class="recommendation-card__jobs">
                            @foreach ($jobRecommendations as $recommendation)
                                <section class="recommendation-card__job">
                                    <h3>{{ is_string($recommendation['title'] ?? null) && filled($recommendation['title']) ? $recommendation['title'] : 'Recommended career path' }}</h3>

                                    @if (is_string($recommendation['reason'] ?? null) && filled($recommendation['reason']))
                                        <p><strong>Why it may fit:</strong> {{ $recommendation['reason'] }}</p>
                                    @endif

                                    @if (is_string($recommendation['evidence'] ?? null) && filled($recommendation['evidence']))
                                        <p><strong>Relevant skills or credentials:</strong> {{ $recommendation['evidence'] }}</p>
                                    @elseif (is_array($recommendation['evidence'] ?? null))
                                        <ul class="suite-evidence">@foreach ($recommendation['evidence'] as $evidence)@if (is_string($evidence))<li>{{ $evidence }}</li>@endif @endforeach</ul>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($recommendationScans->hasPages())
                <nav class="users-directory__pagination" aria-label="PESO job match pages">
                    @if ($recommendationScans->previousPageUrl())
                        <a href="{{ $recommendationScans->previousPageUrl() }}" rel="prev">Previous</a>
                    @else
                        <span aria-disabled="true">Previous</span>
                    @endif

                    <span>Page {{ $recommendationScans->currentPage() }} of {{ $recommendationScans->lastPage() }}</span>

                    @if ($recommendationScans->nextPageUrl())
                        <a href="{{ $recommendationScans->nextPageUrl() }}" rel="next">Next</a>
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
    <aside class="suite-aside"><section class="suite-note"><span class="workspace-eyebrow">FROM POSSIBILITY TO PRACTICE</span><h2>Make your next move.</h2><p>Use your matches as a starting point. Compare the role’s requirements, build a missing skill, and confirm actual openings with PESO or the employer.</p><a href="{{ route('tesda.index') }}">Explore TESDA training ↗</a><a href="{{ route('scanner.index') }}">Add new document evidence ↗</a></section><x-career-chat /></aside>
    </div>
    </div>
</x-app-layout>
