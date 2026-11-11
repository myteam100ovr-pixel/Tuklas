<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · SCHEDULES" title="Training sessions" description="See dates, training centers, and available places." />
    @include('operations.search', ['placeholder' => 'Search programs or centers', 'count' => $sessions->total()])
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if ($canManage)<section class="operations-card"><h2>Add session</h2><form method="POST" action="{{ route('sessions.store') }}">@csrf
        <label>Course <select name="training_program_id" required>@foreach ($programs as $program)<option value="{{ $program->id }}">{{ $program->title }}</option>@endforeach</select></label>
        <label>Center <select name="training_center_id" required>@foreach ($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select></label>
        <label>Starts <input type="datetime-local" name="starts_at" required></label><label>Ends <input type="datetime-local" name="ends_at" required></label>
        <label>Places <input type="number" name="capacity" min="1" required></label><label>Status <select name="status"><option value="open">Open</option><option value="closed">Closed</option></select></label><button type="submit">Save session</button>
    </form></section>@endif
    @forelse ($sessions as $session)<article class="operations-card"><h2>{{ $session->program->title }} <span class="operations-status">{{ ucfirst($session->status) }}</span></h2><p>{{ $session->center->name }} · {{ $session->starts_at->format('M j, Y g:i A') }} – {{ $session->ends_at->format('M j, Y g:i A') }}</p><p>{{ $session->accepted_count }} of {{ $session->capacity }} places accepted</p>
        @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && $session->accepted_count < $session->capacity)<form method="POST" action="{{ route('applications.store', $session) }}">@csrf<button type="submit">Request a place</button></form>@endif
        @if ($canManage)<details><summary>Edit session</summary><form method="POST" action="{{ route('sessions.update', $session) }}">@csrf @method('PUT')
            <label>Course <select name="training_program_id">@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($program->id === $session->training_program_id)>{{ $program->title }}</option>@endforeach</select></label>
            <label>Center <select name="training_center_id">@foreach ($centers as $center)<option value="{{ $center->id }}" @selected($center->id === $session->training_center_id)>{{ $center->name }}</option>@endforeach</select></label>
            <label>Starts <input type="datetime-local" name="starts_at" value="{{ $session->starts_at->format('Y-m-d\TH:i') }}" required></label><label>Ends <input type="datetime-local" name="ends_at" value="{{ $session->ends_at->format('Y-m-d\TH:i') }}" required></label>
            <label>Places <input type="number" name="capacity" min="1" value="{{ $session->capacity }}" required></label><label>Status <select name="status"><option value="open" @selected($session->status === 'open')>Open</option><option value="closed" @selected($session->status === 'closed')>Closed</option></select></label><button type="submit">Update</button>
        </form></details>@endif
    </article>@empty <p class="operations-empty">{{ request()->filled('q') ? 'No sessions match your search.' : 'No sessions available yet.' }}</p> @endforelse
    {{ $sessions->links() }}
</div></x-app-layout>
