<x-app-layout>
    @php($editing = $career->exists)
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="PESO · CAREERS" :title="$editing ? 'Edit career path' : 'Add career path'" description="Describe the path clearly and record the source used to verify it." />
        <form method="POST" action="{{ $editing ? route('admin.careers.update', $career) : route('admin.careers.store') }}" class="trainer-courses__form">
            @csrf
            @if ($editing)@method('PUT')@endif
            @if ($errors->any())<p class="trainer-courses__error" role="alert">Please review the highlighted fields.</p>@endif
            <label>Career title
                <input name="title" type="text" maxlength="255" required value="{{ old('title', $career->title) }}" placeholder="e.g. Agricultural technician">
                @error('title')<small>{{ $message }}</small>@enderror
            </label>
            <label>Description
                <textarea name="description" rows="5" maxlength="3000">{{ old('description', $career->description) }}</textarea>
                @error('description')<small>{{ $message }}</small>@enderror
            </label>
            <div class="trainer-courses__form-row">
                <label>Education or entry level
                    <input name="education_level" type="text" maxlength="60" value="{{ old('education_level', $career->education_level) }}" placeholder="e.g. Senior high school">
                    @error('education_level')<small>{{ $message }}</small>@enderror
                </label>
                <label>Source note
                    <input name="source_note" type="text" maxlength="255" value="{{ old('source_note', $career->source_note) }}" placeholder="Official reference or local source">
                    @error('source_note')<small>{{ $message }}</small>@enderror
                </label>
            </div>
            <label>Visibility
                <select name="status" required>
                    @foreach (['draft' => 'Draft · hidden', 'published' => 'Published · available in Tuklas', 'archived' => 'Archived · hidden'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $career->status ?: 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<small>{{ $message }}</small>@enderror
            </label>
            <div class="trainer-courses__form-actions">
                <button class="trainer-courses__primary" type="submit">{{ $editing ? 'Save changes' : 'Save career path' }}</button>
                <a href="{{ route('admin.careers.index') }}">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
