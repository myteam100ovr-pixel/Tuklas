<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="EXPLORE · CAREERS" title="Career paths" description="Explore published career ideas and the education or training that may help you get started. Confirm current requirements with employers or training providers." />
        <div class="trainer-courses__toolbar">
            <span class="text-sm text-slate-600 dark:text-slate-300">{{ $careers->total() }} published paths</span>
            <a href="{{ route('tesda.index') }}" class="trainer-courses__primary">Explore TESDA training <span aria-hidden="true">↗</span></a>
        </div>
        <section aria-label="Published career paths">
            @forelse ($careers as $career)
                <article class="trainer-courses__card">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $career->title }}</h2>
                        @if ($career->description)<p class="whitespace-pre-wrap">{{ $career->description }}</p>@endif
                        @if ($career->education_level)<p>Education or entry level: {{ $career->education_level }}</p>@endif
                        @if ($career->source_note)<p>Source: {{ $career->source_note }}</p>@endif
                    </div>
                </article>
            @empty
                <div class="trainer-courses__empty">No career paths have been published yet. You can still explore PESO job matches and TESDA training.</div>
            @endforelse
            {{ $careers->links() }}
        </section>
    </div>
</x-app-layout>
