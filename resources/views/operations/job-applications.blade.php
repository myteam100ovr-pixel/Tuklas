<x-app-layout>
    <div class="trainer-courses suite-page operations-page job-applications-page" data-live-job-applications-url="{{ route('admin.job-applications.index') }}">
        <x-workspace-heading eyebrow="PESO · WORK" title="Job applications" description="Review Career Seeker applications and the qualifications they submitted for each opening." />

        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif

        <p class="job-applications-page__count" data-job-application-count>{{ $applications->total() }} {{ \Illuminate\Support\Str::plural('application', $applications->total()) }}</p>

        <section class="job-applications-list" data-job-applications-list data-review-url-template="{{ route('admin.job-applications.update', ['jobApplication' => 'JOB_APPLICATION_ID']) }}" data-review-csrf="{{ csrf_token() }}">
        @forelse ($applications as $application)
            <article class="operations-card job-application-card">
                <div class="job-application-card__header">
                    <div>
                        <p class="workspace-eyebrow">{{ $application->job->employer_name ?: 'Employer not listed' }}</p>
                        <h2>{{ $application->job->title }}</h2>
                        <p>{{ $application->user->name }} · {{ $application->user->email }}</p>
                    </div>
                    <span class="operations-status">{{ ucfirst($application->status) }}</span>
                </div>

                <p class="job-application-card__date">Submitted {{ $application->created_at->format('M j, Y · g:i A') }}</p>

                <section class="job-application-card__response" aria-labelledby="qualification-{{ $application->id }}">
                    <h3 id="qualification-{{ $application->id }}">Qualifications for this job</h3>
                    <p>{{ $application->qualification_summary }}</p>
                </section>

                @if ($application->application_message)
                    <section class="job-application-card__response" aria-labelledby="message-{{ $application->id }}">
                        <h3 id="message-{{ $application->id }}">Message to PESO</h3>
                        <p>{{ $application->application_message }}</p>
                    </section>
                @endif

                @if ($application->decision_reason)
                    <section class="job-application-card__response" aria-label="PESO decision reason">
                        <h3>Reason provided by PESO</h3>
                        <p>{{ $application->decision_reason }}</p>
                    </section>
                @endif

                @if (in_array($application->status, ['submitted', 'reviewing'], true))
                    <form class="job-application-card__review" method="POST" action="{{ route('admin.job-applications.update', $application) }}">
                        @csrf
                        @method('PUT')
                        <label>Status
                            <select name="status" required>
                                <option value="reviewing" @selected($application->status === 'reviewing')>Under review</option>
                                <option value="hired">Hired</option>
                                <option value="not_selected">Not selected</option>
                            </select>
                        </label>
                        <label>Feedback for the Career Seeker <span>Required if not selected</span>
                            <textarea name="decision_reason" minlength="5" maxlength="3000">{{ old('decision_reason') }}</textarea>
                        </label>
                        <button type="submit">Save application decision</button>
                    </form>
                @elseif ($application->reviewer)
                    <p class="job-application-card__reviewed">Reviewed by {{ $application->reviewer->name }}@if ($application->reviewed_at) · {{ $application->reviewed_at->format('M j, Y') }}@endif</p>
                @endif
            </article>
        @empty
            <p class="operations-empty">No job applications have been submitted yet. Applications from Career Seekers will appear here.</p>
        @endforelse
        </section>

        {{ $applications->links() }}
    </div>
</x-app-layout>
