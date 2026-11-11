<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · TRAINEES" title="Training applications" description="Review requests and track each trainee’s status." />
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @forelse ($applications as $application)<article class="operations-card"><h2>{{ $application->program->title }} @if ($application->is_demo)<small class="operations-status">Simulated demo data</small>@endif <span class="operations-status">{{ ucfirst($application->status) }}</span></h2><p>{{ $application->user->name }} · {{ $application->session?->center?->name ?? 'Center not selected' }}</p>
        @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && $application->status === 'pending')<form method="POST" action="{{ route('applications.destroy', $application) }}">@csrf @method('DELETE')<button class="operations-secondary" type="submit">Withdraw request</button></form>@endif
        @if (auth()->user()->hasRole(\App\Enums\Role::SuperAdmin, \App\Enums\Role::TesdaAdmin, \App\Enums\Role::CenterRepresentative) && $application->status === 'pending')<form method="POST" action="{{ route('applications.update', $application) }}">@csrf @method('PUT')<button name="status" value="accepted" type="submit">Accept</button><button class="operations-secondary" name="status" value="declined" type="submit">Decline</button></form>@endif
    </article>@empty <p class="operations-empty">No applications yet.</p> @endforelse
    {{ $applications->links() }}
</div></x-app-layout>
