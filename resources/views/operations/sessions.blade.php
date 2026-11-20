<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · SCHEDULES" title="Training sessions" description="See dates, training centers, and available places." />
    @include('operations.search', ['placeholder' => 'Search programs or centers', 'count' => $sessions->total()])
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if ($canManage)<p class="operations-empty">Only published courses at active centers appear in the Career Seeker catalog. New sessions created here are published schedules when their course is published and the session is open.</p>@endif
    @if ($canManage)@php($creatingSession = old('_operation') === 'create-session')<section class="operations-card"><h2>Add session</h2><form method="POST" action="{{ route('sessions.store') }}">@csrf<input type="hidden" name="_operation" value="create-session">
        <label>Course <select name="training_program_id" required>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($creatingSession && (string) old('training_program_id') === (string) $program->id)>{{ $program->center?->type }} · {{ $program->center?->name }} — {{ $program->title }}</option>@endforeach</select></label>
        <label>Center <select name="training_center_id" required>@foreach ($centers as $center)<option value="{{ $center->id }}" @selected($creatingSession && (string) old('training_center_id') === (string) $center->id)>{{ $center->type }} · {{ $center->name }}</option>@endforeach</select></label>
        <label>Starts <input type="datetime-local" name="starts_at" value="{{ $creatingSession ? old('starts_at') : '' }}" required></label><label>Ends <input type="datetime-local" name="ends_at" value="{{ $creatingSession ? old('ends_at') : '' }}" required></label>
        <label>Maximum places you can cater <input type="number" name="capacity" min="1" value="{{ $creatingSession ? old('capacity') : '' }}" required><small>Only PESO and TESDA can set or change this limit.</small></label><label>Status <select name="status"><option value="open" @selected(! $creatingSession || old('status') === 'open')>Open</option><option value="closed" @selected($creatingSession && old('status') === 'closed')>Closed</option></select></label><button type="submit">Save session</button>
    </form></section>@endif
    @forelse ($sessions as $session)<article class="operations-card"><h2>{{ $session->program->title }} <span class="operations-status">{{ ucfirst($session->status) }}</span></h2><p>{{ $session->center->name }} · {{ $session->starts_at->format('M j, Y g:i A') }} – {{ $session->ends_at->format('M j, Y g:i A') }}</p><p>{{ $session->accepted_count }} of {{ $session->capacity }} places accepted</p>
        @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && $session->accepted_count < $session->capacity)<form method="POST" action="{{ route('applications.store', $session) }}">@csrf<button type="submit">Request a place</button></form>@endif
        @if ($canManage)<details><summary>Edit session</summary><form method="POST" action="{{ route('sessions.update', $session) }}">@csrf @method('PUT')
            <label>Course <select name="training_program_id">@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($program->id === $session->training_program_id)>{{ $program->center?->type }} · {{ $program->center?->name }} — {{ $program->title }}</option>@endforeach</select></label>
            <label>Center <select name="training_center_id">@foreach ($centers as $center)<option value="{{ $center->id }}" @selected($center->id === $session->training_center_id)>{{ $center->type }} · {{ $center->name }}</option>@endforeach</select></label>
            <label>Starts <input type="datetime-local" name="starts_at" value="{{ $session->starts_at->format('Y-m-d\TH:i') }}" required></label><label>Ends <input type="datetime-local" name="ends_at" value="{{ $session->ends_at->format('Y-m-d\TH:i') }}" required></label>
            <label>Maximum places you can cater <input type="number" name="capacity" min="1" value="{{ $session->capacity }}" required><small>Only PESO and TESDA can set or change this limit.</small></label><label>Status <select name="status"><option value="open" @selected($session->status === 'open')>Open</option><option value="closed" @selected($session->status === 'closed')>Closed</option></select></label><button type="submit">Update</button>
        </form></details>
            <details><summary>Delete session</summary>
                <p>Sessions with application history can’t be deleted. Close them to preserve trainee records.</p>
                <form method="POST" action="{{ route('sessions.destroy', $session) }}" onsubmit="return confirm('Delete this training session? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button class="operations-secondary" type="submit">Delete session</button>
                </form>
            </details>
        @endif
    </article>@empty <p class="operations-empty">{{ request()->filled('q') ? 'No sessions match your search.' : 'No sessions available yet.' }}</p> @endforelse
    {{ $sessions->links() }}
</div></x-app-layout>
