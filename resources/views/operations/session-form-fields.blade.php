@php
    $editingValues = $session !== null && old('_operation') === 'edit-session-'.$session->id;
    $useOldValues = $creatingSession
        ? old('_operation') === 'create-session'
        : $editingValues;
    $selectedProgramId = $useOldValues ? old('training_program_id') : ($session?->training_program_id ?? $programs->first()?->id);
    $selectedCenterId = $useOldValues ? old('training_center_id') : ($session?->training_center_id ?? $centers->first()?->id);
    $selectedTrainerId = $useOldValues ? old('trainer_id') : $session?->trainer_id;
    $startsAtValue = $useOldValues ? old('starts_at') : $session?->starts_at?->format('Y-m-d\\TH:i');
    $endsAtValue = $useOldValues ? old('ends_at') : $session?->ends_at?->format('Y-m-d\\TH:i');
    $trainingDates = $useOldValues ? old('training_dates', []) : ($session?->training_dates ?? []);
    $nonWorkingDates = $useOldValues ? old('non_working_dates', []) : ($session?->non_working_dates ?? []);
    $skipWeekends = $useOldValues ? old('skip_weekends') : ($session?->skip_weekends ?? $creatingSession);
    $skipWeekends = filter_var($skipWeekends, FILTER_VALIDATE_BOOLEAN);
@endphp

@if ($session !== null)
    <input type="hidden" name="_operation" value="edit-session-{{ $session->id }}">
@endif

<label>
    Course
    <select name="training_program_id" data-schedule-program required>
        @foreach ($programs as $program)
            <option value="{{ $program->id }}" data-duration-days="{{ $program->duration_days ?? '' }}" data-program-center="{{ $program->training_center_id ?? '' }}" @selected((string) $selectedProgramId === (string) $program->id)>
                {{ $program->center?->type ?? 'Training center' }} · {{ $program->center?->name ?? 'Choose center below' }} — {{ $program->title }}
            </option>
        @endforeach
    </select>
</label>
<label>
    Center
    <select name="training_center_id" data-schedule-center required>
        @foreach ($centers as $center)
            <option value="{{ $center->id }}" @selected((string) $selectedCenterId === (string) $center->id)>{{ $center->type }} · {{ $center->name }}</option>
        @endforeach
    </select>
</label>
<label>
    Assigned trainer
    <select name="trainer_id" data-schedule-trainer>
        <option value="">No trainer selected</option>
        @foreach ($programs as $program)
            @foreach ($program->teachingAssignments as $assignment)
                @php($trainer = $assignment->trainer)
                @if ($trainer && ($trainer->training_center_id === null || (int) $trainer->training_center_id === (int) ($program->training_center_id ?? $selectedCenterId)))
                    <option value="{{ $trainer->id }}" data-assigned-program="{{ $program->id }}" data-trainer-center="{{ $trainer->training_center_id ?? '' }}" @selected((string) $selectedTrainerId === (string) $trainer->id)>
                        {{ $trainer->name }} · TESDA
                    </option>
                @endif
            @endforeach
        @endforeach
    </select>
    <small data-trainer-help>No TESDA staff are assigned to this course yet. Use the Trainers tab to assign staff first.</small>
</label>
<label>
    Starts
    <input type="datetime-local" name="starts_at" data-schedule-start value="{{ $startsAtValue }}" required>
</label>
<label>
    Ends
    <input type="datetime-local" name="ends_at" data-schedule-end value="{{ $endsAtValue }}" required>
</label>

<fieldset class="training-date-planner" data-training-date-planner>
    <legend>Training date plan</legend>
    <p data-training-date-summary aria-live="polite">Choose a course with a listed duration and a start date to generate its training dates.</p>
    <label class="training-date-planner__toggle">
        <input type="checkbox" name="skip_weekends" value="1" data-schedule-skip-weekends @checked($skipWeekends)>
        Skip Saturdays and Sundays
    </label>
    <div class="training-date-planner__excluded" data-non-working-list>
        <span class="training-date-planner__label">Other non-working dates</span>
        @foreach ($nonWorkingDates as $nonWorkingDate)
            <label class="training-date-planner__date">
                <span class="sr-only">Non-working date</span>
                <input type="date" name="non_working_dates[]" value="{{ $nonWorkingDate }}" data-non-working-date>
                <button type="button" class="operations-secondary" data-remove-non-working-date aria-label="Remove non-working date">Remove</button>
            </label>
        @endforeach
    </div>
    <button type="button" class="operations-secondary training-date-planner__add" data-add-non-working-date>Add a non-working date</button>
    <div class="training-date-planner__dates" data-training-date-list aria-live="polite">
        @foreach ($trainingDates as $trainingDate)
            <label class="training-date-planner__date">
                <span>Training day {{ $loop->iteration }}</span>
                <input type="date" name="training_dates[]" value="{{ $trainingDate }}" data-training-date required>
            </label>
        @endforeach
    </div>
    <small>Suggested dates remain editable. Keep them in order; the saved schedule uses the first and last training dates above.</small>
</fieldset>

<label>
    Maximum places you can cater
    <input type="number" name="capacity" min="1" value="{{ $useOldValues ? old('capacity') : ($session?->capacity ?? '') }}" required>
    <small>Only PESO and TESDA can set or change this limit.</small>
</label>
<label
    >{{ __('Status') }}
    <select name="status">
        <option value="open" @selected(($useOldValues ? old('status') : $session?->status ?? 'open') === 'open')>{{ __('Open') }}</option>
        <option value="closed" @selected(($useOldValues ? old('status') : $session?->status) === 'closed')>{{ __('Closed') }}</option>
    </select>
</label>
