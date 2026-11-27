<x-app-layout>
    <div class="trainer-courses suite-page operations-page training-applications-page">
        <x-workspace-heading eyebrow="TESDA · TRAINEES" title="Training applications" description="Review requests and track each trainee’s status." />

        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif

        @php($statusTabs = [
            ['label' => 'All', 'value' => null, 'count' => 'all'],
            ['label' => 'Pending', 'value' => 'pending', 'count' => 'pending'],
            ['label' => 'Accepted', 'value' => 'accepted', 'count' => 'accepted'],
            ['label' => 'Declined', 'value' => 'declined', 'count' => 'declined'],
        ])
        @php($activeStatus = request('status'))

        <div class="training-applications-toolbar flex flex-wrap items-center justify-between gap-3 mb-6">
            <nav class="training-applications-status-tabs" aria-label="Filter by application status">
                @foreach ($statusTabs as $tab)
                    @php($tabQuery = request()->except(['status', 'page']))
                    @if ($tab['value'])@php($tabQuery['status'] = $tab['value'])@endif
                    <a class="training-applications-status-tab {{ $activeStatus === $tab['value'] ? 'is-active' : '' }}" href="{{ route('applications.index', $tabQuery) }}" @if ($activeStatus === $tab['value']) aria-current="page" @endif>
                        {{ $tab['label'] }} <span>{{ $applicationCounts[$tab['count']] }}</span>
                    </a>
                @endforeach
            </nav>

            <form class="training-applications-filters" method="GET" action="{{ route('applications.index') }}">
                @if ($activeStatus)<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                <label>Search trainee or course
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Name or qualification">
                </label>
                <label>Training center
                    <select name="center">
                        <option value="">All Centers</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" @selected((string) request('center') === (string) $center->id)>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Course
                    <select name="course">
                        <option value="">All Courses</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) request('course') === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="training-applications-filter-actions">
                    <button type="submit">Apply filters</button>
                    <a href="{{ route('applications.index') }}">Reset filters</a>
                </div>
            </form>
        </div>

        <div class="training-applications-grid grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($applications as $application)
                <article class="operations-card training-application-card p-5 rounded-xl flex flex-col justify-between gap-3">
                    <header class="training-application-card__header">
                        <h2>{{ $application->program->title }}</h2>
                        <span class="operations-status training-application-status training-application-status--{{ $application->status }}">{{ ucfirst($application->status) }}</span>
                    </header>

                    <div class="training-application-card__body">
                        <p><strong>Trainee:</strong> {{ $application->user->name }}</p>
                        <p><strong>Training center:</strong> {{ $application->session?->center?->name ?? $application->program?->center?->name ?? 'Center not selected' }}</p>
                        <p><strong>Submitted:</strong> {{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</p>
                        @if ($application->decision_reason)
                            <p><strong>Decision reason:</strong> {{ $application->decision_reason }}</p>
                        @endif
                    </div>

                    <footer class="training-application-card__footer">
                        @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && $application->status === 'pending')
                            <form method="POST" action="{{ route('applications.destroy', $application) }}">
                                @csrf
                                @method('DELETE')
                                <button class="operations-secondary" type="submit">Withdraw request</button>
                            </form>
                        @elseif (auth()->user()->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::TesdaAdmin) && in_array($application->status, ['pending', 'accepted', 'declined'], true))
                            @php($availableSessions = $application->program->sessions->filter(fn ($session) => $session->accepted_count < $session->capacity))
                            @php($currentSessionAvailable = $application->session && $availableSessions->contains('id', $application->session->id))

                            @if ($application->status === 'pending')
                                <form class="training-application-card__accept-form" method="POST" action="{{ route('applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    @if ($currentSessionAvailable)
                                        <input type="hidden" name="training_session_id" value="{{ $application->session->id }}">
                                    @elseif ($availableSessions->isNotEmpty())
                                        <label>Intake and training center (optional)
                                            <select name="training_session_id">
                                                <option value="">Accept request and assign an intake later</option>
                                                @foreach ($availableSessions as $session)
                                                    <option value="{{ $session->id }}">{{ $session->starts_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }} · {{ $session->center->name }} · {{ $session->capacity - $session->accepted_count }} places left</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @else
                                        @if ($application->session)<input type="hidden" name="training_session_id" value="">@endif
                                        <p>No available intake has seats right now. You can accept the course request and assign a training date later.</p>
                                    @endif
                                    <button name="status" value="accepted" type="submit">Accept</button>
                                </form>

                                <details class="training-application-card__decline" @if (old('application_id') == $application->id) open @endif>
                                    <summary>Decline</summary>
                                    <form method="POST" action="{{ route('applications.update', $application) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="declined">
                                        <input type="hidden" name="application_id" value="{{ $application->id }}">
                                        <label>Reason for declining
                                            <textarea name="decision_reason" required minlength="5" maxlength="3000">{{ old('application_id') == $application->id ? old('decision_reason') : '' }}</textarea>
                                        </label>
                                        <button class="training-application-card__decline-button" type="submit">Confirm decline</button>
                                    </form>
                                </details>
                            @else
                                <details class="training-application-card__edit" @if (old('application_id') == $application->id) open @endif>
                                    <summary>Edit decision</summary>
                                    <div class="training-application-card__edit-actions">
                                        <form class="training-application-card__accept-form" method="POST" action="{{ route('applications.update', $application) }}">
                                            @csrf
                                            @method('PUT')
                                            @if ($application->status === 'accepted' && $application->session)
                                                <input type="hidden" name="training_session_id" value="{{ $application->session->id }}">
                                            @elseif ($availableSessions->isNotEmpty())
                                                <label>Intake and training center (optional)
                                                    <select name="training_session_id" @required($application->status === 'accepted')>
                                                        <option value="">Accept without assigning an intake</option>
                                                        @foreach ($availableSessions as $session)
                                                            <option value="{{ $session->id }}">{{ $session->starts_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }} · {{ $session->center->name }} · {{ $session->capacity - $session->accepted_count }} places left</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                            @elseif ($application->status === 'declined' && $application->session)
                                                <input type="hidden" name="training_session_id" value="">
                                            @elseif ($application->status === 'accepted' && ! $application->session)
                                                <p>No available intake has seats right now. Add or open a future training date before assigning this trainee.</p>
                                            @endif
                                            <button name="status" value="accepted" type="submit" @disabled($application->status === 'accepted' && ! $application->session && $availableSessions->isEmpty())>Set as accepted</button>
                                        </form>

                                        <form method="POST" action="{{ route('applications.update', $application) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="declined">
                                            <input type="hidden" name="application_id" value="{{ $application->id }}">
                                            <label>Reason for declining
                                                <textarea name="decision_reason" required minlength="5" maxlength="3000">{{ old('application_id') == $application->id ? old('decision_reason') : ($application->decision_reason ?? '') }}</textarea>
                                            </label>
                                            <button class="training-application-card__decline-button" type="submit">Set as declined</button>
                                        </form>
                                    </div>
                                </details>
                            @endif
                        @endif
                    </footer>
                </article>
            @empty
                <p class="operations-empty">
                    @if (request()->hasAny(['status', 'q', 'center', 'course']))
                        No training requests match the current filters. <a href="{{ route('applications.index') }}">Reset filters</a>
                    @else
                        No applications yet.
                    @endif
                </p>
            @endforelse
        </div>

        {{ $applications->links() }}
    </div>
</x-app-layout>
