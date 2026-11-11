<x-app-layout>
    <div class="trainer-courses suite-page operations-page">
        <x-workspace-heading eyebrow="TRAINING · SUPPORT" title="Scholarships" description="Source-checked program information and locally confirmed openings for training and assessment." />
        @include('operations.search', ['placeholder' => 'Search scholarships or providers', 'count' => $scholarships->total()])
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        @php($myScholarshipIds = $myApplications->pluck('scholarship_id')->all())
        @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
            <section class="operations-card" aria-labelledby="my-scholarship-applications">
                <h2 id="my-scholarship-applications">My scholarship applications</h2>
                @forelse ($myApplications as $application)
                    <article class="operations-card">
                        <h3>{{ $application->scholarship->title }} <span class="operations-status">{{ ucfirst($application->status) }}</span> @if ($application->scholarship->is_demo)<small class="operations-status">Simulated demo data</small>@endif</h3>
                        <p>{{ $application->scholarship->provider }}</p>
                        <p>{{ $application->application_message }}</p>
                        @if ($application->review_notes)<p><strong>Review update:</strong> {{ $application->review_notes }}</p>@endif
                        @foreach ($application->documents ?? [] as $index => $document)
                            <a href="{{ route('scholarship-applications.documents', [$application, $index]) }}">{{ $document['name'] }}</a>
                        @endforeach
                    </article>
                @empty
                    <p class="operations-empty">You have not applied to a scholarship yet. Review the requirements below and submit your documents when ready.</p>
                @endforelse
            </section>
        @endif
        @if ($canManage)
            <section class="operations-card" aria-labelledby="received-scholarship-applications">
                <h2 id="received-scholarship-applications">Applications received</h2>
                @forelse ($applications as $application)
                    <article class="operations-card">
                        <h3>{{ $application->scholarship->title }} <span class="operations-status">{{ ucfirst($application->status) }}</span></h3>
                        <p>{{ $application->user->name }} · {{ $application->user->email }}</p>
                        <p>{{ $application->application_message }}</p>
                        @foreach ($application->documents ?? [] as $index => $document)
                            <a href="{{ route('scholarship-applications.documents', [$application, $index]) }}">{{ $document['name'] }}</a>
                        @endforeach
                        <form method="POST" action="{{ route('scholarship-applications.update', $application) }}">@csrf @method('PUT')
                            <label>Status <select name="status">@foreach (['reviewing', 'approved', 'rejected'] as $status)<option value="{{ $status }}" @selected($application->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                            <label>Review notes <textarea name="review_notes">{{ $application->review_notes }}</textarea></label>
                            <button type="submit">Save review</button>
                        </form>
                    </article>
                @empty
                    <p class="operations-empty">No scholarship applications have been submitted yet.</p>
                @endforelse
            </section>
        @endif
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
            <article class="operations-card"><h2>{{ $scholarship->title }} @if ($scholarship->is_demo)<small class="operations-status">Simulated demo data</small>@endif</h2><p>{{ $scholarship->provider }} <span class="operations-status">{{ ucfirst($scholarship->status) }}</span></p><p>{{ $scholarship->description }}</p>
                <p><strong>Who can apply:</strong> {{ $scholarship->eligibility ?: 'Not specified by provider' }}</p>
                <p><strong>Requirements:</strong> {{ $scholarship->requirements ?: 'Not specified by provider' }}</p>
                <p><strong>Assessment:</strong> {{ $scholarship->assessment_type ?: 'Not specified by provider' }}@if ($scholarship->assessment_details) — {{ $scholarship->assessment_details }}@endif</p>
                @if ($scholarship->source_url)<p><a href="{{ $scholarship->source_url }}" target="_blank" rel="noopener noreferrer">Check provider source ↗</a></p>@endif
                @if ($scholarship->deadline)<p>Deadline {{ $scholarship->deadline->format('M j, Y') }}</p>@endif
                @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && ! in_array($scholarship->id, $myScholarshipIds, true))
                    <details><summary>Apply in Tuklas</summary>
                        <p>Write a short application message and attach up to five required documents. Only you and authorized PESO/TESDA staff can access uploaded files.</p>
                        <form method="POST" enctype="multipart/form-data" action="{{ route('scholarship-applications.store', $scholarship) }}">@csrf
                            <label>Application message <textarea name="application_message" minlength="20" maxlength="3000" required>{{ old('application_message') }}</textarea></label>
                            <label>Supporting documents <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple required><span>At least one document. PDF, JPG, or PNG; up to 10 MB per file and five files.</span></label>
                            <button type="submit">Submit scholarship application</button>
                        </form>
                    </details>
                @endif
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
