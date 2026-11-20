<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · TRAINEES" title="Training applications" description="Review requests and track each trainee’s status." />
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @forelse ($applications as $application)<article class="operations-card"><h2>{{ $application->program->title }} <span class="operations-status">{{ ucfirst($application->status) }}</span></h2><p>{{ $application->user->name }} · {{ $application->session?->center?->name ?? $application->program?->center?->name ?? 'Center not selected' }}</p>
        @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && $application->status === 'pending')<form method="POST" action="{{ route('applications.destroy', $application) }}">@csrf @method('DELETE')<button class="operations-secondary" type="submit">Withdraw request</button></form>@endif
        @if (auth()->user()->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::TesdaAdmin) && ($application->status === 'pending' || ($application->status === 'accepted' && ! $application->session)))
            @php($availableSessions = $application->program->sessions->filter(fn ($session) => $session->accepted_count < $session->capacity))
            @php($assigningIntake = $application->status === 'accepted')
            @php($currentSessionAvailable = $application->session && $availableSessions->contains('id', $application->session->id))
            <form method="POST" action="{{ route('applications.update', $application) }}">@csrf @method('PUT')
                @if ($currentSessionAvailable)
                    <input type="hidden" name="training_session_id" value="{{ $application->session->id }}">
                @elseif ($availableSessions->isNotEmpty())
                    <label>{{ $assigningIntake ? 'Assign an intake and training center' : 'Intake and training center (optional)' }}<select name="training_session_id" @if ($assigningIntake) required @endif><option value="">{{ $assigningIntake ? 'Choose a date and available intake' : 'Accept request and assign an intake later' }}</option>@foreach ($availableSessions as $session)<option value="{{ $session->id }}">{{ $session->starts_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }} · {{ $session->center->name }} · {{ $session->capacity - $session->accepted_count }} places left</option>@endforeach</select></label>
                    @if (! $assigningIntake)<small>You can accept the course request now and assign an intake later.</small>@endif
                @else
                    @if ($application->session)<input type="hidden" name="training_session_id" value="">@endif
                    <p>{{ $assigningIntake ? 'No available intake has seats right now. Add or open a future training date before assigning this trainee.' : 'No available intake has seats right now. You can accept the course request and assign a training date later.' }}</p>
                @endif
                @if (! $assigningIntake)
                    <button name="status" value="accepted" type="submit">Accept</button>
                    <button class="operations-secondary" name="status" value="declined" type="submit" formnovalidate>Decline</button>
                @elseif ($availableSessions->isNotEmpty())
                    <button name="status" value="accepted" type="submit">Assign intake</button>
                @endif
            </form>
        @endif
    </article>@empty <p class="operations-empty">No applications yet.</p> @endforelse
    {{ $applications->links() }}
</div></x-app-layout>
