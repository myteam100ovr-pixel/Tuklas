<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="EXPLORE · CAREERS" title="{{ __('Career paths') }}" description="Explore published career ideas and the education or training that may help you get started. Confirm current requirements with employers or training providers." />
        <div class="trainer-courses__toolbar">
            <span class="text-sm text-slate-600 dark:text-slate-300">{{ $careers->total() }} published paths</span>
            <a href="{{ route('tesda.index') }}" class="trainer-courses__primary">{{ __('Explore TESDA training') }} <span aria-hidden="true">↗</span></a>
        </div>
        <section aria-label="Published career paths">
            @forelse ($careers as $career)
                <article class="trainer-courses__card">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $career->title }}</h2>
                        @if ($career->description)<p class="whitespace-pre-wrap">{{ $career->description }}</p>@endif
                        @if ($career->education_level)<p>Education or entry level: {{ $career->education_level }}</p>@endif
                        @if ($career->source_note)<p>Source: {{ $career->source_note }}</p>@endif
                        @php($match = $career->skill_match)
                        @if ($match['status'] === 'ready')
                            <div class="mt-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                <strong>{{ $match['percent'] }}% skills overlap · {{ $match['matched'] }} of {{ $match['total'] }} listed</strong>
                                <p class="mt-1 text-sm">This compares your saved self-assessment choices with the skills PESO listed for this path. It is not a verified qualification or hiring probability.</p>
                                @if ($match['matched_skills'] !== [])<p class="text-sm">Selected in your assessment: {{ implode(', ', $match['matched_skills']) }}</p>@endif
                                @if ($match['unselected_skills'] !== [])<p class="text-sm">Not selected in your assessment: {{ implode(', ', $match['unselected_skills']) }}</p>@endif
                            </div>
                        @elseif ($match['status'] === 'assessment_update_required')
                            <p class="mt-3">Save or update your skills assessment to see a personal skills match.</p>
                            <a href="{{ route('youth.assessment.index') }}">Update skills assessment ↗</a>
                        @else
                            <p class="mt-3">A match percentage is not available because PESO has not listed at least three path skills yet.</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="trainer-courses__empty">No career paths have been published yet. You can still explore PESO job matches and TESDA training.</div>
            @endforelse
            {{ $careers->links() }}
        </section>
    </div>
</x-app-layout>
