<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · ASSESSMENTS" title="Assessment records" description="Authorized staff record schedules and results for accepted trainees." />
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if (auth()->user()->hasRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::TesdaAdmin, \App\Enums\Role::CenterRepresentative))<section class="operations-card"><h2>Add assessment</h2><form method="POST" action="{{ route('assessments.store') }}">@csrf
        <label>Trainee <select name="training_application_id" required>@foreach ($eligibleApplications as $application)<option value="{{ $application->id }}">{{ $application->user->name }} — {{ $application->program->title }}</option>@endforeach</select></label>
        <label>Scheduled at <input type="datetime-local" name="scheduled_at"></label><label>Status <select name="status"><option>scheduled</option><option>completed</option><option>cancelled</option></select></label>
        <label>Result <select name="result"><option value="">Pending</option><option>passed</option><option>failed</option></select></label><label>Notes <textarea name="notes"></textarea></label><button type="submit">Save assessment</button>
    </form></section>@endif
    @forelse ($records as $record)<article class="operations-card"><h2>{{ $record->application->program->title }}</h2><p>{{ $record->application->user->name }} · {{ $record->application->session?->center?->name }} · {{ ucfirst($record->status) }}</p><p>{{ $record->scheduled_at?->format('M j, Y g:i A') }} · Result: {{ $record->result ?? 'Pending' }}</p><p>{{ $record->notes }}</p>
        @if (auth()->user()->hasRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::TesdaAdmin, \App\Enums\Role::CenterRepresentative))<details><summary>Update record</summary><form method="POST" action="{{ route('assessments.update', $record) }}">@csrf @method('PUT')
            <input type="hidden" name="training_application_id" value="{{ $record->training_application_id }}"><label>Scheduled at <input type="datetime-local" name="scheduled_at" value="{{ $record->scheduled_at?->format('Y-m-d\TH:i') }}"></label>
            <label>Status <select name="status">@foreach (['scheduled', 'completed', 'cancelled'] as $status)<option @selected($record->status === $status)>{{ $status }}</option>@endforeach</select></label>
            <label>Result <select name="result"><option value="">Pending</option><option @selected($record->result === 'passed')>passed</option><option @selected($record->result === 'failed')>failed</option></select></label><label>Notes <textarea name="notes">{{ $record->notes }}</textarea></label><button type="submit">Update</button>
        </form></details>@endif
    </article>@empty <p>No assessments recorded yet.</p> @endforelse
    {{ $records->links() }}
</div></x-app-layout>
