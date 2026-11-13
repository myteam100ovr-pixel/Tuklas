<x-app-layout>
    <div class="trainer-courses suite-page operations-page job-opportunities" data-live-jobs-url="{{ route('opportunities.index', request()->only('q', 'page')) }}" data-hire-csrf="{{ csrf_token() }}">
        <x-workspace-heading eyebrow="PESO · WORK" title="Job opportunities" description="Source-checked openings and clearly marked simulated records for workflow testing." />
        <aside class="suite-note"><strong>Want more current openings?</strong> Search the official DOLE PhilJobNet vacancy portal. Openings shown below have been checked by PESO staff.<br><a href="https://philjobnet.gov.ph/job-vacancies/" target="_blank" rel="noopener noreferrer">Browse live PhilJobNet vacancies ↗</a></aside>
        @include('operations.search', ['placeholder' => 'Search job titles or employers', 'count' => $jobs->total()])
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        <p data-live-status role="status" hidden></p>
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        @if ($canManage)
            @php($creatingJob = old('_operation') === 'create-job')
            <section class="operations-card"><h2>Add opening</h2><form method="POST" action="{{ route('admin.opportunities.store') }}">@csrf<input type="hidden" name="_operation" value="create-job">
                <label>Employer or organization <input name="employer_name" required maxlength="255" value="{{ $creatingJob ? old('employer_name') : '' }}"></label>
                <label><input type="checkbox" name="employer_verified" value="1" @checked($creatingJob && old('employer_verified'))> I checked that this employer and opening are current</label>
                <label>Title <input name="title" required value="{{ $creatingJob ? old('title') : '' }}"></label><label>Pay / compensation <input name="compensation" maxlength="100" placeholder="Enter exactly as the source states" value="{{ $creatingJob ? old('compensation') : '' }}"></label><label>Location <input name="location" value="{{ $creatingJob ? old('location') : '' }}"></label>
                <label>Description <textarea name="description" required>{{ $creatingJob ? old('description') : '' }}</textarea></label><label>Requirements <textarea name="requirements">{{ $creatingJob ? old('requirements') : '' }}</textarea></label>
                <label>Application URL <input name="application_url" type="url" value="{{ $creatingJob ? old('application_url') : '' }}"></label><label>Closing date <input name="closes_at" type="date" value="{{ $creatingJob ? old('closes_at') : '' }}"></label>
                <label>Source URL <input name="source_url" type="url" placeholder="Official vacancy listing" value="{{ $creatingJob ? old('source_url') : '' }}"></label><label>Source checked date <input name="source_checked_at" type="date" value="{{ $creatingJob ? old('source_checked_at') : '' }}"></label>
                <label>Status <select name="status"><option value="draft" @selected(! $creatingJob || old('status') === 'draft')>Draft</option><option value="published" @selected($creatingJob && old('status') === 'published')>Publish</option><option value="closed" @selected($creatingJob && old('status') === 'closed')>Closed</option></select></label>
                <button class="trainer-courses__primary" type="submit">Save opening</button>
            </form></section>
        @endif
        <section class="job-opportunities__list" aria-label="Job opportunities">
        @forelse ($jobs as $job)
            <article class="operations-card" data-job-id="{{ $job->id }}" data-hire-url="{{ $canManage ? route('admin.opportunities.hire', $job) : '' }}"><h2>{{ $job->title }} @if ($job->recommended_for_me ?? false)<small class="operations-status">Recommended by PESO</small>@endif @unless ($canManage)<small class="operations-status" data-my-hired @if (! $job->recommendedUsers->contains(fn ($member) => $member->pivot->hired_at !== null)) hidden @endif>Hired · recorded by PESO</small>@endunless</h2><p>{{ $job->employer_name ?: $job->employer?->name }} @if ($job->location) · {{ $job->location }} @endif @if ($job->compensation) · {{ $job->compensation }} @endif <span class="operations-status">{{ ucfirst($job->status) }}</span></p><p>{{ $job->description }}</p>
                @if ($job->requirements)<p><strong>Requirements:</strong> {{ $job->requirements }}</p>@endif
                @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
                    @php($myJobApplication = $job->applications->first())
                    @if ($myJobApplication)
                        <p class="job-application-status" data-my-job-application role="status">Application {{ $myJobApplication->status === 'reviewing' ? 'Under review' : ucfirst(str_replace('_', ' ', $myJobApplication->status)) }}@if ($myJobApplication->decision_reason)<br><strong>PESO feedback:</strong> {{ $myJobApplication->decision_reason }}@endif</p>
                    @elseif ($job->status === 'published' && ($job->closes_at === null || $job->closes_at->gte(today())) && ($job->is_demo || $job->employer_verified || $job->employer?->is_verified))
                        <details class="job-application">
                            <summary>Apply for this job</summary>
                            <p>{{ $job->requirements ? 'Use the listed requirements to explain your relevant qualifications, training, and experience.' : 'No specific requirements were listed. Describe the relevant skills, training, and experience you would bring.' }}</p>
                            <form method="POST" action="{{ route('job-applications.store', $job) }}">
                                @csrf
                                <label for="job-qualifications-{{ $job->id }}">How your qualifications meet this job’s requirements
                                    <textarea id="job-qualifications-{{ $job->id }}" name="qualification_summary" minlength="20" maxlength="3000" required>{{ old('qualification_summary') }}</textarea>
                                </label>
                                <label for="job-application-message-{{ $job->id }}">Message to PESO <span>Optional</span>
                                    <textarea id="job-application-message-{{ $job->id }}" name="application_message" maxlength="3000">{{ old('application_message') }}</textarea>
                                </label>
                                <button type="submit">Submit application</button>
                            </form>
                        </details>
                    @endif
                @endif
                @if ($job->closes_at)<p>Closes {{ $job->closes_at->format('M j, Y') }}</p>@endif
                @if ($job->source_url)<p><a href="{{ $job->source_url }}" target="_blank" rel="noopener noreferrer">Source listing ↗</a> @if ($job->source_checked_at)<span>· Checked {{ $job->source_checked_at->format('M j, Y') }}</span>@endif</p>@endif
                @if ($job->application_url)<a href="{{ $job->application_url }}" target="_blank" rel="noopener noreferrer">{{ $job->application_url === $job->source_url ? 'View opening and application details ↗' : 'Apply with employer ↗' }}</a>@endif
                @if ($canManage)
                    <section class="job-candidate-workflow" aria-label="Career seeker hiring workflow">
                        <div class="job-candidate-workflow__matches" data-hiring-list>
                            <h3>Career seeker matches</h3>
                            <ul>
                                @forelse ($job->recommendedUsers as $member)
                                    <li>
                                        <div class="job-candidate-workflow__person"><strong>{{ $member->name }}</strong><span>{{ $member->email }}</span></div>
                                        @if ($member->pivot->hired_at)
                                            <strong class="operations-status">Hired {{ \Illuminate\Support\Carbon::parse($member->pivot->hired_at)->format('M j, Y') }}</strong>
                                        @elseif ($member->pivot->not_selected_at)
                                            <strong class="operations-status">Not selected · {{ $member->pivot->not_selected_reason }}</strong>
                                        @else
                                            <div class="job-candidate-workflow__decisions">
                                                <form method="POST" action="{{ route('admin.opportunities.hire', $job) }}">
                                                    @csrf<input type="hidden" name="youth_email" value="{{ $member->email }}">
                                                    <button type="submit">Mark hired</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.opportunities.not-selected', $job) }}">
                                                    @csrf<input type="hidden" name="youth_email" value="{{ $member->email }}">
                                                    <label>Reason they were not selected<textarea name="reason" required minlength="5" maxlength="3000"></textarea></label>
                                                    <button type="submit">Mark not selected</button>
                                                </form>
                                            </div>
                                        @endif
                                    </li>
                                @empty
                                    <li>No seekers have been matched to this opening yet. Choose a seeker to send them this opportunity.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="job-candidate-workflow__recommend">
                            <h3>Choose a career seeker</h3>
                            <p>Select an active Career Seeker account to add this opening to their job matches.</p>
                            <form method="POST" action="{{ route('admin.opportunities.recommend', $job) }}">
                                @csrf
                                <label for="career-seeker-{{ $job->id }}">Career seeker</label>
                                <select id="career-seeker-{{ $job->id }}" name="youth_email" required @disabled($careerSeekers->isEmpty())>
                                    <option value="">{{ $careerSeekers->isEmpty() ? 'No active career seeker accounts found' : 'Select a career seeker' }}</option>
                                    @foreach ($careerSeekers as $careerSeeker)
                                        <option value="{{ $careerSeeker->email }}" @selected(old('youth_email') === $careerSeeker->email)>
                                            {{ $careerSeeker->name }} · {{ $careerSeeker->email }}{{ $careerSeeker->youthProfile?->employment_status ? ' · '.$careerSeeker->youthProfile->employment_status : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" @disabled($careerSeekers->isEmpty())>Add job match</button>
                            </form>
                            @if ($careerSeekers->isEmpty())
                                <p class="job-candidate-workflow__empty">No active Career Seeker accounts are available. Active accounts will appear here automatically.</p>
                            @endif
                        </div>
                    </section>
                    <details><summary>Edit opening</summary><form method="POST" action="{{ route('admin.opportunities.update', $job) }}">@csrf @method('PUT')
                    <label>Employer or organization <input name="employer_name" required maxlength="255" value="{{ $job->employer_name ?: $job->employer?->name }}"></label>
                    <label><input type="checkbox" name="employer_verified" value="1" @checked($job->employer_verified || $job->employer?->is_verified)> I checked that this employer and opening are current</label>
                    <label>Title <input name="title" required value="{{ $job->title }}"></label><label>Pay / compensation <input name="compensation" maxlength="100" value="{{ $job->compensation }}"></label><label>Location <input name="location" value="{{ $job->location }}"></label>
                    <label>Description <textarea name="description" required>{{ $job->description }}</textarea></label><label>Requirements <textarea name="requirements">{{ $job->requirements }}</textarea></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $job->application_url }}"></label><label>Closing date <input name="closes_at" type="date" value="{{ $job->closes_at?->toDateString() }}"></label>
                    <label>Source URL <input name="source_url" type="url" value="{{ $job->source_url }}"></label><label>Source checked date <input name="source_checked_at" type="date" value="{{ $job->source_checked_at?->toDateString() }}"></label>
                    <label>Status <select name="status">@foreach (['draft', 'published', 'closed'] as $status)<option value="{{ $status }}" @selected($job->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><button type="submit">Update</button>
                </form></details>@endif
            </article>
        @empty <p class="operations-empty">{{ request()->filled('q') ? 'No jobs match your search.' : 'No published opportunities yet.' }}</p> @endforelse
        </section>
        {{ $jobs->links() }}
    </div>
</x-app-layout>
