<x-app-layout>
    <div class="trainer-courses suite-page operations-page">
        <x-workspace-heading eyebrow="TESDA · SCHEDULES" title="Training sessions" description="See dates, training centers, and available places." />
        @include('operations.search', ['placeholder' => 'Search programs or centers', 'count' => $sessions->total()])

        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif
        @if ($canManage)
            <p class="operations-empty">Only published courses at active centers appear in the Career Seeker catalog. New sessions created here are published schedules when their course is published and the session is open.</p>
            <section class="operations-card">
                <h2>Add session</h2>
                <form method="POST" action="{{ route('sessions.store') }}" data-training-schedule-form>
                    @csrf
                    <input type="hidden" name="_operation" value="create-session">
                    @include('operations.session-form-fields', ['session' => null, 'creatingSession' => true])
                    <button type="submit">Save session</button>
                </form>
            </section>
        @endif

        @forelse ($sessions as $session)
            <article class="operations-card">
                <h2>{{ $session->program->title }} <span class="operations-status">{{ ucfirst($session->status) }}</span></h2>
                <p>{{ $session->center->name }} · {{ $session->starts_at->format('M j, Y g:i A') }} – {{ $session->ends_at->format('M j, Y g:i A') }}</p>
                <p><strong>Trainer:</strong> {{ $session->trainer?->name ?? 'Not assigned' }}</p>
                @if ($session->training_dates)
                    <p><strong>Training dates:</strong> {{ collect($session->training_dates)->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('M j, Y'))->implode(' · ') }}</p>
                @endif
                <p>{{ $session->accepted_count }} of {{ $session->capacity }} places accepted</p>
                @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
                    @php($myApplication = $session->applications->first())
                    @if ($myApplication && in_array($myApplication->status, ['pending', 'accepted'], true))
                        <p class="operations-status" role="status">Already requested · {{ ucfirst($myApplication->status) }}</p>
                    @elseif ($session->accepted_count < $session->capacity)
                        <form method="POST" action="{{ route('applications.store', $session) }}">@csrf<button type="submit">Request course</button></form>
                    @else
                        <p class="operations-status" role="status">Fully occupied</p>
                    @endif
                @endif
                @if ($canManage)
                    <details>
                        <summary>Edit session</summary>
                        <form method="POST" action="{{ route('sessions.update', $session) }}" data-training-schedule-form>
                            @csrf
                            @method('PUT')
                            @include('operations.session-form-fields', ['session' => $session, 'creatingSession' => false])
                            <button type="submit">Update</button>
                        </form>
                    </details>
                    <details>
                        <summary>Delete session</summary>
                        <p>Sessions with application history can’t be deleted. Close them to preserve trainee records.</p>
                        <form method="POST" action="{{ route('sessions.destroy', $session) }}" onsubmit="return confirm('Delete this training session? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button class="operations-secondary" type="submit">Delete session</button>
                        </form>
                    </details>
                @endif
            </article>
        @empty
            <p class="operations-empty">{{ request()->filled('q') ? 'No sessions match your search.' : 'No sessions available yet.' }}</p>
        @endforelse
        {{ $sessions->links() }}
    </div>
</x-app-layout>
