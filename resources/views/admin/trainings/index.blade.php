<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="{{ auth()->user()->hasRole(\App\Enums\Role::TesdaAdmin) ? 'TESDA · TRAINING' : 'PESO · TRAINING' }}" title="Training catalog" description="Review registered provider entries. Confirm requirements and schedules with the provider before publishing; published programs appear in the public TESDA catalog." />
        @if (session('status'))<p class="trainer-courses__feedback" role="status">{{ session('status') }}</p>@endif
        <div class="trainer-courses__toolbar">
            <div class="flex flex-wrap gap-3 text-sm">
                @foreach (['All' => null, 'Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'] as $label => $value)
                    <a href="{{ $value ? route('admin.trainings.index', ['status' => $value]) : route('admin.trainings.index') }}" @if ($statusFilter === $value) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </div>
            <a href="{{ route('admin.trainings.create') }}" class="trainer-courses__primary">Add program <span aria-hidden="true">↗</span></a>
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-300"><a class="underline" href="{{ route('tesda.index') }}#nc-offerings">View the public TESDA catalog ↗</a></p>
        <section aria-label="Training programs">
            <div class="trainer-courses__section-heading"><h2>{{ $programs->total() }} programs</h2></div>
            @forelse ($programs as $program)
                <article class="trainer-courses__card">
                    <div>
                        <span class="trainer-courses__status">{{ ucfirst($program->status) }}</span>
                        <h3>{{ $program->title }}</h3>
                        <p>{{ $program->nc_level ?: 'NC level not specified' }} · {{ $program->center?->name ?? 'No center assigned' }} · {{ $program->duration_days ? $program->duration_days.' training days' : 'Duration not specified' }} · Managed by {{ $program->creator?->name ?? 'Unassigned' }} · Updated {{ $program->updated_at->format('M j, Y') }}</p>
                        @if ($program->source_reference === 'https://www.tesda.gov.ph/Tvi/Result')
                            <a href="{{ $program->source_reference }}" target="_blank" rel="noopener noreferrer">Source: TESDA registered provider directory ↗</a>
                        @elseif ($program->source_reference)
                            <p>Source: {{ $program->source_reference }}</p>
                        @endif
                    </div>
                    <a href="{{ route('admin.trainings.edit', $program) }}">Edit program <span aria-hidden="true">↗</span></a>
                </article>
            @empty
                <div class="trainer-courses__empty">No training programs match this view. Add one as a draft or ask a TESDA trainer to publish a verified offering.</div>
            @endforelse
            {{ $programs->links() }}
        </section>
    </div>
</x-app-layout>
