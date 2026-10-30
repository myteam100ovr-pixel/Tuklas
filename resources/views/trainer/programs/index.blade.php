<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="TESDA · TRAINER" title="Courses I teach" description="Choose national TESDA qualifications or published local courses to teach, and manage the offerings you publish." />

        @if (session('status'))<p class="trainer-courses__feedback" role="status">{{ session('status') }}</p>@endif

        <div class="trainer-courses__toolbar">
            <a href="{{ route('trainer.programs.create') }}" class="trainer-courses__primary">Add a course <span aria-hidden="true">↗</span></a>
            <a href="{{ route('tesda.index') }}#nc-offerings">Choose a course to teach</a>
        </div>

        <section aria-labelledby="teaching-qualifications-title">
            <div class="trainer-courses__section-heading"><h2 id="teaching-qualifications-title">TESDA qualifications I teach</h2><span>{{ $trainerQualifications->count() }} selected</span></div>
            @forelse ($trainerQualifications as $qualification)
                <article class="trainer-courses__card"><div><span class="trainer-courses__status">{{ $qualification->nc_level }}</span><h3>{{ $qualification->title }}</h3></div><form method="POST" action="{{ route('trainer.qualifications.destroy', $qualification->qualification_id) }}">@csrf @method('DELETE')<button class="tesda-application-button is-secondary" type="submit">Remove</button></form></article>
            @empty
                <div class="trainer-courses__empty">No national qualifications selected yet. Browse the TESDA catalog to choose courses to teach.</div>
            @endforelse
        </section>

        <section aria-labelledby="offered-courses-title">
            <div class="trainer-courses__section-heading">
                <h2 id="offered-courses-title">Courses you manage</h2>
                <span>{{ $programs->total() }} total</span>
            </div>
            @forelse ($programs as $program)
                <article class="trainer-courses__card">
                    <div>
                        <span class="trainer-courses__status">{{ ucfirst($program->status) }}</span>
                        <h3>{{ $program->title }}</h3>
                        <p>{{ $program->nc_level ?: 'NC level not set' }} · Updated {{ $program->updated_at->format('M j, Y') }}</p>
                    </div>
                    <a href="{{ route('trainer.programs.edit', $program) }}">Edit course <span aria-hidden="true">↗</span></a>
                </article>
            @empty
                <div class="trainer-courses__empty">No courses yet. Add one as a draft or publish a verified offering.</div>
            @endforelse
            {{ $programs->links() }}
        </section>

        <section class="trainer-courses__applications" aria-labelledby="my-applications-title">
            <div class="trainer-courses__section-heading">
                <h2 id="my-applications-title">Local courses I teach</h2>
                <span>{{ $teachingAssignments->count() }} selected</span>
            </div>
            <p>Choose from published TESDA Lingayen offerings. This list records your teaching choices in Tuklas.</p>
            @forelse ($teachingAssignments as $assignment)
                <article class="trainer-courses__card">
                    <div>
                        <span class="trainer-courses__status">Teaching</span>
                        <h3>{{ $assignment->program?->title ?? 'Course no longer available' }}</h3>
                        <p>Chosen {{ $assignment->created_at->format('M j, Y') }}</p>
                    </div>
                </article>
            @empty
                <div class="trainer-courses__empty">No teaching courses selected yet. Browse published offerings to choose one.</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
