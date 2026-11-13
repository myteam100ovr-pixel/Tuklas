@php
    $editingProgram = $program->exists;
    $operation = $editingProgram
        ? 'update-course-'.$program->id
        : 'create-course-'.$center->id;
    $values = old('_operation') === $operation ? old() : [];
    $action = $editingProgram
        ? route('centers.programs.update', [$center, $program])
        : route('centers.programs.store', $center);
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($editingProgram)
        @method('PUT')
    @endif
    <input type="hidden" name="_operation" value="{{ $operation }}">

    <label>Course title
        <input name="title" maxlength="255" required value="{{ $values['title'] ?? $program->title }}" placeholder="e.g. Cookery NC II">
    </label>

    <label>NC level
        <select name="nc_level">
            <option value="">Not specified</option>
            @foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)
                <option value="{{ $level }}" @selected(($values['nc_level'] ?? $program->nc_level) === $level)>{{ $level }}</option>
            @endforeach
        </select>
    </label>

    <label>Training hours
        <input type="number" name="duration_hours" min="1" max="65535" value="{{ $values['duration_hours'] ?? $program->duration_hours }}">
    </label>

    <label>Training days
        <input type="number" name="duration_days" min="1" max="65535" value="{{ $values['duration_days'] ?? $program->duration_days }}">
    </label>

    <label>Description
        <textarea name="description" maxlength="3000" rows="3">{{ $values['description'] ?? $program->description }}</textarea>
    </label>

    <label>Entry requirements
        <textarea name="requirements" maxlength="3000" rows="3">{{ $values['requirements'] ?? $program->requirements }}</textarea>
    </label>

    <label>Schedule note
        <input name="schedule_note" maxlength="1000" value="{{ $values['schedule_note'] ?? $program->schedule_note }}" placeholder="Confirm current dates with the provider">
    </label>

    <label>Source or verification reference
        <input name="source_reference" maxlength="255" value="{{ $values['source_reference'] ?? $program->source_reference }}" placeholder="Official notice or provider reference">
    </label>

    <label>Visibility
        <select name="status" required>
            @foreach (['draft' => 'Draft · hidden', 'published' => 'Published · shown in TESDA catalog', 'archived' => 'Archived · hidden'] as $value => $label)
                <option value="{{ $value }}" @selected(($values['status'] ?? $program->status ?: 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <button type="submit">{{ $editingProgram ? 'Save course changes' : 'Add course' }}</button>
</form>
