<x-app-layout>
    @php($editing = $program->exists)
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="TESDA · TRAINER" :title="$editing ? 'Edit offered course' : 'Add an offered course'" description="Keep published course information current so people can make informed training plans." />

        <form method="POST" action="{{ $editing ? route('trainer.programs.update', $program) : route('trainer.programs.store') }}" class="trainer-courses__form">
            @csrf
            @if ($editing)@method('PUT')@endif
            @if ($errors->any())<p class="trainer-courses__error" role="alert">Please review the highlighted fields.</p>@endif

            <label>Course title
                <input name="title" type="text" maxlength="255" required value="{{ old('title', $program->title) }}" placeholder="e.g. Cookery NC II">
                @error('title')<small>{{ $message }}</small>@enderror
            </label>
            <div class="trainer-courses__form-row">
                <label>NC level
                    <select name="nc_level">
                        <option value="">Not specified</option>
                        @foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)
                            <option value="{{ $level }}" @selected(old('nc_level', $program->nc_level) === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    @error('nc_level')<small>{{ $message }}</small>@enderror
                </label>
                <label>Training hours
                    <input name="duration_hours" type="number" min="1" max="65535" value="{{ old('duration_hours', $program->duration_hours) }}">
                    @error('duration_hours')<small>{{ $message }}</small>@enderror
                </label>
            </div>
            <label>Description
                <textarea name="description" rows="4" maxlength="3000">{{ old('description', $program->description) }}</textarea>
                @error('description')<small>{{ $message }}</small>@enderror
            </label>
            <label>Entry requirements
                <textarea name="requirements" rows="3" maxlength="3000">{{ old('requirements', $program->requirements) }}</textarea>
                @error('requirements')<small>{{ $message }}</small>@enderror
            </label>
            <label>Schedule note
                <input name="schedule_note" type="text" maxlength="1000" value="{{ old('schedule_note', $program->schedule_note) }}" placeholder="Confirm the latest dates with TESDA Lingayen">
                @error('schedule_note')<small>{{ $message }}</small>@enderror
            </label>
            <label>Source or verification reference
                <input name="source_reference" type="text" maxlength="255" value="{{ old('source_reference', $program->source_reference) }}" placeholder="Official page, notice, or internal reference">
                @error('source_reference')<small>{{ $message }}</small>@enderror
            </label>
            <label>Visibility
                <select name="status" required>
                    @foreach (['draft' => 'Draft · only you can see it', 'published' => 'Published · shown in TESDA catalog', 'archived' => 'Archived · hidden from catalog'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $program->status ?: 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<small>{{ $message }}</small>@enderror
            </label>

            <div class="trainer-courses__form-actions">
                <button class="trainer-courses__primary" type="submit">{{ $editing ? 'Save changes' : 'Save course' }}</button>
                <a href="{{ route('trainer.programs.index') }}">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
