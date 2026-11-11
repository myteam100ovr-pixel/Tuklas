<x-app-layout>
    <div class="trainer-courses suite-page operations-page">
        <x-workspace-heading eyebrow="TRAINING · SUPPORT" title="Scholarships" description="Verified support opportunities for training and assessment." />
        @include('operations.search', ['placeholder' => 'Search scholarships or providers', 'count' => $scholarships->total()])
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        @if ($canManage)
            @php($creatingScholarship = old('_operation') === 'create-scholarship')
            <section class="operations-editor" aria-labelledby="add-scholarship-heading">
                <div class="operations-editor__intro">
                    <h2 id="add-scholarship-heading">Add scholarship</h2>
                    <p>Share verified funding opportunities with career seekers.</p>
                </div>
                <form class="operations-editor__form" method="POST" action="{{ route('scholarships.store') }}">@csrf<input type="hidden" name="_operation" value="create-scholarship">
                    <label>Title <input name="title" required value="{{ $creatingScholarship ? old('title') : '' }}"></label>
                    <label>Provider <input name="provider" required value="{{ $creatingScholarship ? old('provider') : '' }}"></label>
                    <label class="operations-editor__wide">Description <textarea name="description" required>{{ $creatingScholarship ? old('description') : '' }}</textarea></label>
                    <label class="operations-editor__wide">Who can apply? <textarea name="eligibility" placeholder="Explain who qualifies. Enter 'Not specified by provider' if unconfirmed.">{{ $creatingScholarship ? old('eligibility') : '' }}</textarea></label>
                    <label class="operations-editor__wide">Application requirements <textarea name="requirements" placeholder="List documents or conditions the provider requires.">{{ $creatingScholarship ? old('requirements') : '' }}</textarea></label>
                    <label>Assessment type <select name="assessment_type"><option value="">Not specified by provider</option>@foreach (['None', 'Exam', 'Interview', 'Skills assessment', 'Other'] as $type)<option value="{{ $type }}" @selected($creatingScholarship && old('assessment_type') === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label class="operations-editor__wide">Assessment instructions <textarea name="assessment_details" placeholder="When, where, and how to complete it, if confirmed.">{{ $creatingScholarship ? old('assessment_details') : '' }}</textarea></label>
                    <label class="operations-editor__wide">Official information source <span>Required to publish. Use the provider’s current scholarship page or notice.</span><input name="source_url" type="url" placeholder="https://" value="{{ $creatingScholarship ? old('source_url') : '' }}"></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $creatingScholarship ? old('application_url') : '' }}"></label>
                    <label>Deadline <input name="deadline" type="date" value="{{ $creatingScholarship ? old('deadline') : '' }}"></label>
                    <label>Status <select name="status"><option value="draft" @selected(! $creatingScholarship || old('status') === 'draft')>Draft</option><option value="published" @selected($creatingScholarship && old('status') === 'published')>Publish as verified</option><option value="closed" @selected($creatingScholarship && old('status') === 'closed')>Closed</option></select></label>
                    <div class="operations-editor__actions"><button class="trainer-courses__primary" type="submit">Save scholarship</button></div>
                </form>
            </section>
        @endif
        @forelse ($scholarships as $scholarship)
            <article class="operations-card"><h2>{{ $scholarship->title }}</h2><p>{{ $scholarship->provider }} <span class="operations-status">{{ ucfirst($scholarship->status) }}</span></p><p>{{ $scholarship->description }}</p>
                <p><strong>Who can apply:</strong> {{ $scholarship->eligibility ?: 'Not specified by provider' }}</p>
                <p><strong>Requirements:</strong> {{ $scholarship->requirements ?: 'Not specified by provider' }}</p>
                <p><strong>Assessment:</strong> {{ $scholarship->assessment_type ?: 'Not specified by provider' }}@if ($scholarship->assessment_details) — {{ $scholarship->assessment_details }}@endif</p>
                @if ($scholarship->source_url)<p><a href="{{ $scholarship->source_url }}" target="_blank" rel="noopener noreferrer">Check provider source ↗</a></p>@endif
                @if ($scholarship->deadline)<p>Deadline {{ $scholarship->deadline->format('M j, Y') }}</p>@endif
                @if ($scholarship->application_url)<a href="{{ $scholarship->application_url }}" target="_blank" rel="noopener noreferrer">Apply ↗</a>@endif
                @if ($canManage)<details><summary>Edit</summary><form method="POST" action="{{ route('scholarships.update', $scholarship) }}">@csrf @method('PUT')
                    <label>Title <input name="title" required value="{{ $scholarship->title }}"></label><label>Provider <input name="provider" required value="{{ $scholarship->provider }}"></label>
                    <label>Description <textarea name="description" required>{{ $scholarship->description }}</textarea></label><label>Who can apply? <textarea name="eligibility">{{ $scholarship->eligibility }}</textarea></label>
                    <label>Requirements <textarea name="requirements">{{ $scholarship->requirements }}</textarea></label><label>Assessment type <select name="assessment_type"><option value="">Not specified by provider</option>@foreach (['None', 'Exam', 'Interview', 'Skills assessment', 'Other'] as $type)<option value="{{ $type }}" @selected($scholarship->assessment_type === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label>Assessment instructions <textarea name="assessment_details">{{ $scholarship->assessment_details }}</textarea></label><label>Official source URL <input name="source_url" type="url" value="{{ $scholarship->source_url }}"></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $scholarship->application_url }}"></label><label>Deadline <input name="deadline" type="date" value="{{ $scholarship->deadline?->toDateString() }}"></label>
                    <label>Status <select name="status">@foreach (['draft', 'published', 'closed'] as $status)<option value="{{ $status }}" @selected($scholarship->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><button type="submit">Update</button>
                </form></details>@endif
            </article>
        @empty <p class="operations-empty">{{ request()->filled('q') ? 'No scholarships match your search.' : 'No published scholarships yet.' }}</p> @endforelse
        {{ $scholarships->links() }}
    </div>
</x-app-layout>
