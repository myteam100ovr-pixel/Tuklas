<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="TRAINING · SUPPORT" title="Scholarships" description="Verified support opportunities for training and assessment." />
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        @if ($canManage)<section class="trainer-courses__card"><h2>Add scholarship</h2><form method="POST" action="{{ route('scholarships.store') }}">@csrf
            <label>Title <input name="title" required></label><label>Provider <input name="provider" required></label>
            <label>Description <textarea name="description" required></textarea></label><label>Eligibility <textarea name="eligibility"></textarea></label>
            <label>Application URL <input name="application_url" type="url"></label><label>Deadline <input name="deadline" type="date"></label>
            <label>Status <select name="status"><option value="draft">Draft</option><option value="published">Publish as verified</option><option value="closed">Closed</option></select></label>
            <button class="trainer-courses__primary" type="submit">Save scholarship</button>
        </form></section>@endif
        @forelse ($scholarships as $scholarship)
            <article class="trainer-courses__card"><h2>{{ $scholarship->title }}</h2><p>{{ $scholarship->provider }} · {{ $scholarship->status }}</p><p>{{ $scholarship->description }}</p>
                @if ($scholarship->eligibility)<p><strong>Eligibility:</strong> {{ $scholarship->eligibility }}</p>@endif
                @if ($scholarship->deadline)<p>Deadline {{ $scholarship->deadline->format('M j, Y') }}</p>@endif
                @if ($scholarship->application_url)<a href="{{ $scholarship->application_url }}" target="_blank" rel="noopener noreferrer">Apply ↗</a>@endif
                @if ($canManage)<details><summary>Edit</summary><form method="POST" action="{{ route('scholarships.update', $scholarship) }}">@csrf @method('PUT')
                    <label>Title <input name="title" required value="{{ $scholarship->title }}"></label><label>Provider <input name="provider" required value="{{ $scholarship->provider }}"></label>
                    <label>Description <textarea name="description" required>{{ $scholarship->description }}</textarea></label><label>Eligibility <textarea name="eligibility">{{ $scholarship->eligibility }}</textarea></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $scholarship->application_url }}"></label><label>Deadline <input name="deadline" type="date" value="{{ $scholarship->deadline?->toDateString() }}"></label>
                    <label>Status <select name="status">@foreach (['draft', 'published', 'closed'] as $status)<option value="{{ $status }}" @selected($scholarship->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><button type="submit">Update</button>
                </form></details>@endif
            </article>
        @empty <p>No published scholarships yet.</p> @endforelse
        {{ $scholarships->links() }}
    </div>
</x-app-layout>
