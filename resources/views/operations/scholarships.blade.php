<x-app-layout>
    <div class="trainer-courses suite-page operations-page scholarships-page">
        <x-workspace-heading eyebrow="TRAINING · SUPPORT" title="Scholarships" description="Scholarships for courses offered by PTC and PSAT, with confirmed requirements, available places, and application progress." />
        @include('operations.search', ['placeholder' => 'Search scholarships or providers', 'count' => $scholarships->total()])
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        @php($myScholarshipIds = $myApplications->pluck('scholarship_id')->all())
        @php($approvalLabel = fn ($status) => ['pending' => 'Pending', 'approved' => 'Approved', 'failed' => 'Failed', 'rejected' => 'Failed', 'cancelled' => 'Canceled', 'not_recorded' => 'Not recorded'][$status] ?? 'Not recorded')

        @if (auth()->user()->hasRole(\App\Enums\Role::Youth))
            <section class="operations-card scholarship-applications" aria-labelledby="my-scholarship-applications">
                <h2 id="my-scholarship-applications">My scholarship applications</h2>
                @forelse ($myApplications as $application)
                    @php($applicationStatus = ['submitted' => 'Pending', 'reviewing' => 'Pending', 'approved' => 'Approved', 'cancelled' => 'Canceled', 'rejected' => 'Failed'][$application->status] ?? 'Pending')
                    <article class="operations-card">
                        <h3>{{ $application->scholarship->title }} <span class="operations-status">{{ $applicationStatus }}</span></h3>
                        <p>{{ $application->scholarship->provider }}</p>
                        @if ($application->scholarship->trainingProgram)<p><strong>Course:</strong> {{ $application->scholarship->trainingProgram->title }}</p>@endif
                        @if ($application->scholarship->center)<p><strong>Training center:</strong> {{ $application->scholarship->center->type }} · {{ $application->scholarship->center->name }}</p>@endif
                        <p><strong>Training center approval:</strong> {{ $approvalLabel($application->center_approval_status) }}@if ($application->centerApprover) · {{ $application->centerApprover->name }}@endif</p>
                        @if ($application->center_approval_notes)<p>{{ $application->center_approval_notes }}</p>@endif
                        <p><strong>TESDA trainer approval:</strong> {{ $approvalLabel($application->trainer_approval_status) }}@if ($application->trainerApprover) · {{ $application->trainerApprover->name }}@endif</p>
                        @if ($application->trainer_approval_notes)<p>{{ $application->trainer_approval_notes }}</p>@endif
                        <p>{{ $application->application_message }}</p>
                        @if ($application->review_notes)
                            <p><strong>{{ in_array($application->status, ['approved', 'rejected'], true) ? 'Final TESDA/admin decision reason' : 'Latest review reason' }}@if ($application->reviewer) · {{ $application->reviewer->name }}@endif:</strong> {{ $application->review_notes }}</p>
                        @endif
                        @foreach ($application->documents ?? [] as $index => $document)
                            <a href="{{ route('scholarship-applications.documents', [$application, $index]) }}">{{ $document['name'] }}</a>
                        @endforeach
                        @if (in_array($application->status, ['submitted', 'reviewing'], true))
                            <form method="POST" action="{{ route('scholarship-applications.cancel', $application) }}">@csrf @method('DELETE')
                                <button type="submit">Cancel application</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="operations-empty">You have not applied to a scholarship yet. Review the requirements and available places below.</p>
                @endforelse
            </section>
        @endif

        @if ($canManage)
            <section class="operations-card scholarship-applications" aria-labelledby="received-scholarship-applications">
                <h2 id="received-scholarship-applications">Applications received</h2>
                <p class="scholarship-approval-help">Center and trainer approvals require separate TESDA accounts: one assigned to the center and a different one assigned to the course. An application is automatically approved after both approvals if its message is complete and all uploaded files are available. Accepting does not require a reason; declining opens a reason prompt. Admin and TESDA can also override the final decision.
                    @if (auth()->user()->hasRole(\App\Enums\Role::Admin))
                        <a href="{{ route('admin.users.create') }}">Create a TESDA approver account</a>, assign it to the center, then assign trainer accounts to their courses.
                    @endif
                </p>
                @if ($errors->any())
                    <p class="scholarship-review-error" role="alert">{{ $errors->first() }}</p>
                @endif
                @forelse ($applications as $application)
                    @php($applicationStatus = ['submitted' => 'Pending', 'reviewing' => 'Pending', 'approved' => 'Approved', 'cancelled' => 'Canceled', 'rejected' => 'Failed'][$application->status] ?? 'Pending')
                    <article class="operations-card">
                        <h3>{{ $application->scholarship->title }} <span class="operations-status">{{ $applicationStatus }}</span></h3>
                        <p>{{ $application->user->name }} · {{ $application->user->email }}</p>
                        @if ($application->scholarship->trainingProgram)<p><strong>Course:</strong> {{ $application->scholarship->trainingProgram->title }}</p>@endif
                        <p><strong>Center approval:</strong> {{ $approvalLabel($application->center_approval_status) }}@if ($application->centerApprover) · {{ $application->centerApprover->name }}@endif</p>
                        <p><strong>Trainer approval:</strong> {{ $approvalLabel($application->trainer_approval_status) }}@if ($application->trainerApprover) · {{ $application->trainerApprover->name }}@endif</p>
                        @if ($application->review_notes)
                            <p><strong>{{ in_array($application->status, ['approved', 'rejected'], true) ? 'Final decision reason' : 'Latest review reason' }}@if ($application->reviewer) · {{ $application->reviewer->name }}@endif:</strong> {{ $application->review_notes }}</p>
                        @endif
                        @if ($application->center_approval_notes)<p><strong>Center decision reason:</strong> {{ $application->center_approval_notes }}</p>@endif
                        @if ($application->trainer_approval_notes)<p><strong>Trainer decision reason:</strong> {{ $application->trainer_approval_notes }}</p>@endif
                        <p>{{ $application->application_message }}</p>
                        <p><strong>Scholarship requirements:</strong> {{ $application->scholarship->requirements ?: 'Not specified by provider.' }}</p>
                        @foreach ($application->documents ?? [] as $index => $document)
                            <a href="{{ route('scholarship-applications.documents', [$application, $index]) }}">{{ $document['name'] }}</a>
                        @endforeach
                        @if ($application->is_ready_for_approval)
                            <p class="scholarship-review-readiness" role="status">The application message and uploaded files are available. Compare the files with the scholarship requirements above before accepting.</p>
                        @else
                            <p class="scholarship-review-blocked" role="status">Acceptance is disabled until the application message is complete and every uploaded file is available.</p>
                        @endif
                        @if ($application->can_approve_center || $application->can_approve_trainer)
                            <section class="operations-card" aria-label="TESDA application approvals">
                                @foreach (['center' => 'Training center', 'trainer' => 'TESDA trainer'] as $stage => $label)
                                    @if (($stage === 'center' && $application->can_approve_center) || ($stage === 'trainer' && $application->can_approve_trainer))
                                        <form method="POST" action="{{ route('scholarship-applications.update', $application) }}" data-scholarship-review-form>@csrf @method('PUT')
                                            <input type="hidden" name="stage" value="{{ $stage }}">
                                            <button type="submit" name="decision" value="approved" @disabled(! $application->is_ready_for_approval)>Accept as {{ strtolower($label) }}</button>
                                            <button type="button" data-review-decline-open>Decline as {{ strtolower($label) }}</button>
                                            @include('operations.partials.scholarship-decline-dialog', [
                                                'dialogId' => 'scholarship-decline-'.$application->id.'-'.$stage,
                                                'decision' => 'failed',
                                            ])
                                        </form>
                                    @endif
                                @endforeach
                            </section>
                        @endif
                        @if ($application->can_override_final)
                            <section class="operations-card" aria-label="Final TESDA or administrator decision">
                                <h3>Final decision or override</h3>
                                <p>Accepting does not require a reason. Declining requires a reason, which will be shown to the Career Seeker.</p>
                                <form method="POST" action="{{ route('scholarship-applications.update', $application) }}" data-scholarship-review-form>@csrf @method('PUT')
                                    <input type="hidden" name="stage" value="final">
                                    <button type="submit" name="decision" value="approved" @disabled(! $application->is_ready_for_approval)>Accept application</button>
                                    <button type="button" data-review-decline-open>Decline application</button>
                                    @include('operations.partials.scholarship-decline-dialog', [
                                        'dialogId' => 'scholarship-decline-'.$application->id.'-final',
                                        'decision' => 'rejected',
                                    ])
                                </form>
                            </section>
                        @endif
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
                    <p>Choose a published PTC or PSAT course, confirm its scholarship places, then request TESDA center and trainer approval.</p>
                </div>
                <form class="operations-editor__form" method="POST" action="{{ route('scholarships.store') }}">@csrf<input type="hidden" name="_operation" value="create-scholarship">
                    <label>Title <input name="title" required value="{{ $creatingScholarship ? old('title') : '' }}"></label>
                    <label>Provider <input name="provider" required value="{{ $creatingScholarship ? old('provider') : '' }}"></label>
                    <label>PTC or PSAT training center <span>Required for approval.</span><select name="training_center_id" required><option value="">Select a center</option>@foreach ($centers as $center)<option value="{{ $center->id }}" @selected(($creatingScholarship ? old('training_center_id') : auth()->user()->training_center_id) == $center->id)>{{ $center->type }} · {{ $center->name }}</option>@endforeach</select></label>
                    <label>Course receiving the scholarship <span>Choose a published course from PTC or PSAT.</span><select name="training_program_id" required><option value="">Select a course</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($creatingScholarship && old('training_program_id') == $program->id)>{{ $program->center->type }} · {{ $program->center->name }} — {{ $program->title }}</option>@endforeach</select></label>
                    <label>Available scholarship slots <span>Use the number confirmed by the provider. Applications reserve places in submission order.</span><input name="slot_limit" type="number" min="1" max="500" required value="{{ $creatingScholarship ? old('slot_limit') : '' }}"></label>
                    <label class="operations-editor__wide">Description <textarea name="description" required>{{ $creatingScholarship ? old('description') : '' }}</textarea></label>
                    <label class="operations-editor__wide">Who can apply? <textarea name="eligibility" placeholder="Explain who qualifies. Enter 'Not specified by provider' if unconfirmed.">{{ $creatingScholarship ? old('eligibility') : '' }}</textarea></label>
                    <label class="operations-editor__wide">Application requirements <textarea name="requirements" placeholder="List documents or conditions the provider requires.">{{ $creatingScholarship ? old('requirements') : '' }}</textarea></label>
                    <label>Assessment type <select name="assessment_type"><option value="">Not specified by provider</option>@foreach (['None', 'Exam', 'Interview', 'Skills assessment', 'Other'] as $type)<option value="{{ $type }}" @selected($creatingScholarship && old('assessment_type') === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label class="operations-editor__wide">Assessment instructions <textarea name="assessment_details" placeholder="When, where, and how to complete it, if confirmed.">{{ $creatingScholarship ? old('assessment_details') : '' }}</textarea></label>
                    <label class="operations-editor__wide">Official information source <span>Required to request TESDA approvals. Use the provider’s current scholarship page or notice.</span><input name="source_url" type="url" placeholder="https://" value="{{ $creatingScholarship ? old('source_url') : '' }}"></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $creatingScholarship ? old('application_url') : '' }}"></label>
                    <label>Deadline <input name="deadline" type="date" value="{{ $creatingScholarship ? old('deadline') : '' }}"></label>
                    <label>Next step <select name="status"><option value="draft" @selected(! $creatingScholarship || old('status') === 'draft')>Save draft</option><option value="review" @selected($creatingScholarship && old('status') === 'review')>Submit for TESDA center and trainer approval</option><option value="closed" @selected($creatingScholarship && old('status') === 'closed')>Closed</option></select></label>
                    <p class="operations-editor__wide">Each application is reviewed by a TESDA account assigned to the center, then a different TESDA trainer assigned to the course. Applicants see both decisions and the overall status.</p>
                    <div class="operations-editor__actions"><button class="trainer-courses__primary" type="submit">Save scholarship</button></div>
                </form>
            </section>
        @endif

        @forelse ($scholarships as $scholarship)
            <article class="operations-card">
                <h2>{{ $scholarship->title }}</h2>
                <p>{{ $scholarship->provider }} <span class="operations-status">{{ $scholarship->status === 'review' ? 'Awaiting TESDA approvals' : ucfirst($scholarship->status) }}</span></p>
                <p>{{ $scholarship->description }}</p>
                @if ($scholarship->trainingProgram)<p><strong>Course:</strong> {{ $scholarship->trainingProgram->title }}</p>@endif
                @if ($scholarship->center)<p><strong>Training center:</strong> {{ $scholarship->center->type }} · {{ $scholarship->center->name }}</p>@endif
                @if ($scholarship->slot_limit)
                    <p><strong>Available slots:</strong> {{ $scholarship->is_full ? 'Fully occupied' : $scholarship->available_slots.' of '.$scholarship->slot_limit }}</p>
                @else
                    <p><strong>Availability:</strong> Not confirmed by the provider.</p>
                @endif
                <p><strong>Who can apply:</strong> {{ $scholarship->eligibility ?: 'Not specified by provider' }}</p>
                <p><strong>Requirements:</strong> {{ $scholarship->requirements ?: 'Not specified by provider' }}</p>
                <p><strong>Assessment:</strong> {{ $scholarship->assessment_type ?: 'Not specified by provider' }}@if ($scholarship->assessment_details) — {{ $scholarship->assessment_details }}@endif</p>
                @if ($scholarship->center_approval_status || $scholarship->trainer_approval_status)
                    <p><strong>Listing center approval:</strong> {{ $approvalLabel($scholarship->center_approval_status) }}@if ($scholarship->centerApprover) · {{ $scholarship->centerApprover->name }}@endif</p>
                    @if ($scholarship->center_approval_notes)<p>{{ $scholarship->center_approval_notes }}</p>@endif
                    <p><strong>Listing trainer approval:</strong> {{ $approvalLabel($scholarship->trainer_approval_status) }}@if ($scholarship->trainerApprover) · {{ $scholarship->trainerApprover->name }}@endif</p>
                    @if ($scholarship->trainer_approval_notes)<p>{{ $scholarship->trainer_approval_notes }}</p>@endif
                @elseif ($scholarship->status === 'published')
                    <p><strong>Listing approvals:</strong> Approval history was not recorded for this existing listing.</p>
                @endif
                @if ($scholarship->status === 'review' && ($scholarship->can_approve_center || $scholarship->can_approve_trainer))
                    <section class="operations-card" aria-label="TESDA scholarship listing approvals">
                        @if ($scholarship->can_approve_center)<form method="POST" action="{{ route('scholarships.approve', $scholarship) }}">@csrf<input type="hidden" name="stage" value="center"><label>Center review note <textarea name="notes" maxlength="3000"></textarea></label><button type="submit" name="decision" value="approved">Approve listing for center</button><button type="submit" name="decision" value="rejected">Return listing to draft</button></form>@endif
                        @if ($scholarship->can_approve_trainer)<form method="POST" action="{{ route('scholarships.approve', $scholarship) }}">@csrf<input type="hidden" name="stage" value="trainer"><label>Trainer review note <textarea name="notes" maxlength="3000"></textarea></label><button type="submit" name="decision" value="approved">Approve listing as trainer</button><button type="submit" name="decision" value="rejected">Return listing to draft</button></form>@endif
                    </section>
                @endif
                @if ($scholarship->source_url)<p><a href="{{ $scholarship->source_url }}" target="_blank" rel="noopener noreferrer">Check provider source ↗</a></p>@endif
                @if ($scholarship->deadline)<p>Deadline {{ $scholarship->deadline->format('M j, Y') }}</p>@endif
                @if (auth()->user()->hasRole(\App\Enums\Role::Youth) && ! in_array($scholarship->id, $myScholarshipIds, true))
                    @if ($scholarship->is_full)
                        <p role="status"><strong>This scholarship is fully occupied.</strong> You may refer to another private training center listed below.</p>
                    @elseif (! $scholarship->slot_limit || ! $scholarship->center || ! $scholarship->trainingProgram)
                        <p>Applications are not available yet because the course or slot count has not been confirmed by the provider.</p>
                    @else
                        <details><summary>Apply in Tuklas</summary>
                            <p>First come, first served: your place is reserved when your application is submitted. The reservation stays while the TESDA center and a different trainer review your requirements. Only you and authorized TESDA staff can access uploaded files.</p>
                            <form method="POST" enctype="multipart/form-data" action="{{ route('scholarship-applications.store', $scholarship) }}">@csrf
                                <label>Application message <textarea name="application_message" minlength="20" maxlength="3000" required>{{ old('application_message') }}</textarea></label>
                                <label>Supporting documents <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple required><span>At least one document. PDF, JPG, or PNG; up to 10 MB per file and five files.</span></label>
                                <button type="submit">Submit and reserve a place</button>
                            </form>
                        </details>
                    @endif
                @endif
                @if ($scholarship->is_full && $privateCenters->isNotEmpty())
                    <section aria-label="Other private training centers">
                        <h3>Other private training centers</h3>
                        <ul>@foreach ($privateCenters as $privateCenter)<li><strong>{{ $privateCenter->name }}</strong>@if ($privateCenter->address) · {{ $privateCenter->address }}@endif@if ($privateCenter->programs->isNotEmpty())<span> — Courses: {{ $privateCenter->programs->pluck('title')->take(5)->join(', ') }}</span>@endif</li>@endforeach</ul>
                    </section>
                @elseif ($scholarship->is_full)
                    <p>Check the <a href="https://www.tesda.gov.ph/tvi" target="_blank" rel="noopener noreferrer">TESDA directory of registered training centers ↗</a> for other providers.</p>
                @endif
                @if ($scholarship->application_url)<a href="{{ $scholarship->application_url }}" target="_blank" rel="noopener noreferrer">Apply externally ↗</a>@endif
                @if ($canManage)<details><summary>Edit</summary><form method="POST" action="{{ route('scholarships.update', $scholarship) }}">@csrf @method('PUT')
                    <label>Title <input name="title" required value="{{ $scholarship->title }}"></label><label>Provider <input name="provider" required value="{{ $scholarship->provider }}"></label>
                    <label>PTC or PSAT training center <select name="training_center_id" required><option value="">Select a center</option>@foreach ($centers as $center)<option value="{{ $center->id }}" @selected($scholarship->training_center_id == $center->id)>{{ $center->type }} · {{ $center->name }}</option>@endforeach</select></label>
                    <label>Course receiving the scholarship <select name="training_program_id" required><option value="">Select a course</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected($scholarship->training_program_id == $program->id)>{{ $program->center->type }} · {{ $program->center->name }} — {{ $program->title }}</option>@endforeach</select></label>
                    <label>Available scholarship slots <input name="slot_limit" type="number" min="1" max="500" required value="{{ $scholarship->slot_limit }}"></label>
                    <label>Description <textarea name="description" required>{{ $scholarship->description }}</textarea></label><label>Who can apply? <textarea name="eligibility">{{ $scholarship->eligibility }}</textarea></label>
                    <label>Requirements <textarea name="requirements">{{ $scholarship->requirements }}</textarea></label><label>Assessment type <select name="assessment_type"><option value="">Not specified by provider</option>@foreach (['None', 'Exam', 'Interview', 'Skills assessment', 'Other'] as $type)<option value="{{ $type }}" @selected($scholarship->assessment_type === $type)>{{ $type }}</option>@endforeach</select></label>
                    <label>Assessment instructions <textarea name="assessment_details">{{ $scholarship->assessment_details }}</textarea></label><label>Official source URL <span>Required to request TESDA approvals.</span><input name="source_url" type="url" value="{{ $scholarship->source_url }}"></label>
                    <label>Application URL <input name="application_url" type="url" value="{{ $scholarship->application_url }}"></label><label>Deadline <input name="deadline" type="date" value="{{ $scholarship->deadline?->toDateString() }}"></label>
                    <label>Next step <select name="status"><option value="draft" @selected($scholarship->status === 'draft')>Save draft</option><option value="review" @selected($scholarship->status === 'review')>Submit or resubmit for TESDA approvals</option>@if ($scholarship->status === 'published')<option value="published" selected>Published — resubmit changes for approval</option>@endif<option value="closed" @selected($scholarship->status === 'closed')>Closed</option></select></label><button type="submit">Update</button>
                </form></details>@endif
                @if ($canManage)
                    <details>
                        <summary>Delete scholarship</summary>
                        <p>Listings with applicant history can’t be deleted. Close those listings to keep their records.</p>
                        <form method="POST" action="{{ route('scholarships.destroy', $scholarship) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Confirm delete</button>
                        </form>
                    </details>
                @endif
            </article>
        @empty <p class="operations-empty">{{ request()->filled('q') ? 'No scholarships match your search.' : 'No published scholarships yet.' }}</p> @endforelse
        {{ $scholarships->links() }}
    </div>
</x-app-layout>
