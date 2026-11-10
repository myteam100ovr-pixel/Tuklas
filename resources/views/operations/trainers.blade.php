<x-app-layout><div class="trainer-courses suite-page">
    <x-workspace-heading eyebrow="TESDA · STAFF" title="Assigned trainers" description="Assign trainers to published programs at their training center." />
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    <section class="trainer-courses__card"><h2>Assign trainer</h2>
        <form method="POST" action="{{ route('center-trainers.store') }}">@csrf
            <label>Trainer <select name="user_id" required>@foreach ($trainers as $trainer)<option value="{{ $trainer->id }}">{{ $trainer->name }}</option>@endforeach</select></label>
            <label>Program <select name="training_program_id" required>@foreach ($programs as $program)<option value="{{ $program->id }}">{{ $program->title }}</option>@endforeach</select></label>
            <button type="submit">Assign trainer</button>
        </form>
    </section>
    @forelse ($assignments as $assignment)
        <article class="trainer-courses__card"><h2>{{ $assignment->trainer->name }}</h2><p>{{ $assignment->program->title }}</p>
            <form method="POST" action="{{ route('center-trainers.destroy', $assignment) }}">@csrf @method('DELETE')<button type="submit">Remove assignment</button></form>
        </article>
    @empty <p>No trainer assignments yet.</p> @endforelse
    {{ $assignments->links() }}
</div></x-app-layout>
