<x-app-layout>
    <div class="trainer-courses suite-page operations-page">
        <x-workspace-heading eyebrow="PESO · WORK" title="Job opportunities" description="Verified employer openings published by PESO." />
        <aside class="suite-note"><strong>Want more current openings?</strong> Search the official DOLE PhilJobNet vacancy portal. Openings shown below have been checked by PESO staff.<br><a href="https://philjobnet.gov.ph/job-vacancies/" target="_blank" rel="noopener noreferrer">Browse live PhilJobNet vacancies ↗</a></aside>
        @include('operations.search', ['placeholder' => 'Search job titles or employers', 'count' => $jobs->total()])
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
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
        @forelse ($jobs as $job)
            <article class="operations-card"><h2>{{ $job->title }} @if ($job->recommended_for_me ?? false)<small class="operations-status">Recommended by PESO</small>@endif</h2><p>{{ $job->employer_name ?: $job->employer?->name }} @if ($job->location) · {{ $job->location }} @endif @if ($job->compensation) · {{ $job->compensation }} @endif <span class="operations-status">{{ ucfirst($job->status) }}</span></p><p>{{ $job->description }}</p>
                @if ($job->requirements)<p><strong>Requirements:</strong> {{ $job->requirements }}</p>@endif
                @if ($job->closes_at)<p>Closes {{ $job->closes_at->format('M j, Y') }}</p>@endif
                @if ($job->source_url)<p><a href="{{ $job->source_url }}" target="_blank" rel="noopener noreferrer">Source listing ↗</a> @if ($job->source_checked_at)<span>· Checked {{ $job->source_checked_at->format('M j, Y') }}</span>@endif</p>@endif
                @if ($job->application_url)<a href="{{ $job->application_url }}" target="_blank" rel="noopener noreferrer">{{ $job->application_url === $job->source_url ? 'View opening and application details ↗' : 'Apply with employer ↗' }}</a>@endif
                @if ($canManage)<form method="POST" action="{{ route('admin.opportunities.recommend', $job) }}">@csrf<label>Recommend to career seeker email <input type="email" name="youth_email" required></label><button type="submit">Recommend</button></form><details><summary>Edit opening</summary><form method="POST" action="{{ route('admin.opportunities.update', $job) }}">@csrf @method('PUT')
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
        {{ $jobs->links() }}
    </div>
</x-app-layout>
