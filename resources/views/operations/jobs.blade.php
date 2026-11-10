<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="PESO · WORK" title="Job opportunities" description="Verified employer openings published by PESO." />
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        @if ($canManage)
            <a href="{{ route('admin.employers.index') }}">Manage employers</a>
            <section class="trainer-courses__card"><h2>Add opening</h2><form method="POST" action="{{ route('admin.opportunities.store') }}">@csrf
                <label>Verified employer <select name="employer_id" required>@foreach ($employers as $employer)<option value="{{ $employer->id }}">{{ $employer->name }}</option>@endforeach</select></label>
                <label>Title <input name="title" required></label><label>Location <input name="location"></label>
                <label>Description <textarea name="description" required></textarea></label><label>Requirements <textarea name="requirements"></textarea></label>
                <label>Application URL <input name="application_url" type="url"></label><label>Closing date <input name="closes_at" type="date"></label>
                <label>Status <select name="status"><option value="draft">Draft</option><option value="published">Publish</option><option value="closed">Closed</option></select></label>
                <button class="trainer-courses__primary" type="submit">Save opening</button>
            </form></section>
        @endif
        @forelse ($jobs as $job)
            <article class="trainer-courses__card"><h2>{{ $job->title }} @if ($job->recommended_for_me ?? false)<small>Recommended by PESO</small>@endif</h2><p>{{ $job->employer->name }} · {{ $job->location }} · {{ $job->status }}</p><p>{{ $job->description }}</p>
                @if ($job->requirements)<p><strong>Requirements:</strong> {{ $job->requirements }}</p>@endif
                @if ($job->closes_at)<p>Closes {{ $job->closes_at->format('M j, Y') }}</p>@endif
                @if ($job->application_url)<a href="{{ $job->application_url }}" target="_blank" rel="noopener noreferrer">Apply with employer ↗</a>@endif
                @if ($canManage)<form method="POST" action="{{ route('admin.opportunities.recommend', $job) }}">@csrf<label>Recommend to career seeker email <input type="email" name="youth_email" required></label><button type="submit">Recommend</button></form><details><summary>Edit opening</summary><form method="POST" action="{{ route('admin.opportunities.update', $job) }}">@csrf @method('PUT')
                    <label>Employer <select name="employer_id">@foreach ($employers as $employer)<option value="{{ $employer->id }}" @selected($employer->id === $job->employer_id)>{{ $employer->name }}</option>@endforeach</select></label>
                    <label>Title <input name="title" required value="{{ $job->title }}"></label><label>Location <input name="location" value="{{ $job->location }}"></label>
                    <label>Description <textarea name="description" required>{{ $job->description }}</textarea></label><label>Requirements <textarea name="requirements">{{ $job->requirements }}</textarea></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $job->application_url }}"></label><label>Closing date <input name="closes_at" type="date" value="{{ $job->closes_at?->toDateString() }}"></label>
                    <label>Status <select name="status">@foreach (['draft', 'published', 'closed'] as $status)<option value="{{ $status }}" @selected($job->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><button type="submit">Update</button>
                </form></details>@endif
            </article>
        @empty <p>No published opportunities yet.</p> @endforelse
        {{ $jobs->links() }}
    </div>
</x-app-layout>
