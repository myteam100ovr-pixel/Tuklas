<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · STAFF" title="Program staff assignments" description="Assign active TESDA staff to published programs at a training center." />
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @forelse ($centers as $center)
        @php
            $eligibleTrainers = $trainers->filter(fn ($trainer) => $trainer->training_center_id === null || $trainer->training_center_id === $center->id);
        @endphp
        <section class="operations-card"><h2>{{ $center->name }}</h2><p>Choose a staff member and a published program at this center.</p>
            @if ($eligibleTrainers->isNotEmpty() && $center->programs->isNotEmpty())
                <form method="POST" action="{{ route('center-trainers.store') }}">@csrf
                    <label>TESDA staff member <select name="user_id" required>
                        <option value="">Select staff</option>
                        @foreach ($eligibleTrainers as $trainer)<option value="{{ $trainer->id }}" @selected(old('user_id') == $trainer->id)>{{ $trainer->name }} · {{ $trainer->role->label() }}</option>@endforeach
                    </select></label>
                    <label>Program <select name="training_program_id" required>
                        <option value="">Select program</option>
                        @foreach ($center->programs as $program)<option value="{{ $program->id }}" @selected(old('training_program_id') == $program->id)>{{ $program->title }}</option>@endforeach
                    </select></label>
                    <button type="submit">Assign trainer</button>
                </form>
            @else
                <p class="operations-empty">No eligible staff or published programs are available for this center.</p>
            @endif
        </section>
    @empty <p class="operations-empty">No active training centers with published programs are available.</p> @endforelse
    <section><h2>Current assignments</h2>
        @forelse ($assignments as $assignment)
            <article class="operations-card"><h3>{{ $assignment->trainer->name }}</h3><p>{{ $assignment->program->title }} · {{ $assignment->program->center?->name }}</p>
                <form method="POST" action="{{ route('center-trainers.destroy', $assignment) }}">@csrf @method('DELETE')<button class="operations-secondary" type="submit">Remove assignment</button></form>
            </article>
        @empty <p class="operations-empty">No trainer assignments yet.</p> @endforelse
    </section>
    {{ $assignments->links() }}
</div></x-app-layout>
