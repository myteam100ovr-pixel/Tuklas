<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Events\WorkspaceChanged;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $canManage = $user->hasRole(Role::Admin, Role::TesdaAdmin);
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $scholarships = Scholarship::query()->when(! $canManage, fn ($query) => $query->published()->where('is_demo', false))
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $user->training_center_id))
            ->with(['center:id,name,type', 'trainingProgram:id,title,training_center_id', 'centerApprover:id,name', 'trainerApprover:id,name'])
            ->withCount(['applications as occupied_slots' => fn ($query) => $query->whereIn('status', ['submitted', 'reviewing', 'approved'])])
            ->when($search !== '', fn ($query) => $query->where(fn ($items) => $items->where('title', 'like', '%'.$search.'%')->orWhere('provider', 'like', '%'.$search.'%')))
            ->latest()->paginate(50)->withQueryString();
        $myApplications = $user->hasRole(Role::Youth)
            ? ScholarshipApplication::query()->with([
                'scholarship:id,title,provider,is_demo,training_center_id,training_program_id,slot_limit',
                'scholarship.center:id,name,type',
                'scholarship.trainingProgram:id,title,training_center_id',
                'centerApprover:id,name',
                'trainerApprover:id,name',
                'reviewer:id,name',
            ])->where('user_id', $request->user()->id)->latest()->get()
            : collect();
        $applications = $canManage
            ? ScholarshipApplication::query()->with([
                'scholarship:id,title,provider,is_demo,training_center_id,training_program_id,slot_limit',
                'scholarship.center:id,name,type',
                'scholarship.trainingProgram:id,title,training_center_id',
                'user:id,name,email',
                'centerApprover:id,name',
                'trainerApprover:id,name',
                'reviewer:id,name',
            ])->when(
                $user->isCenterScopedTesda(),
                fn ($query) => $query->whereHas('scholarship', fn ($scholarships) => $scholarships->where('training_center_id', $user->training_center_id)),
            )->latest()->limit(100)->get()
            : collect();
        $centers = $canManage
            ? TrainingCenter::query()->where('is_active', true)
                ->where('is_demo', false)
                ->whereIn('type', ['PTC', 'PSAT'])
                ->when($user->isCenterScopedTesda(), fn ($query) => $query->whereKey($user->training_center_id))
                ->orderBy('name')->get(['id', 'name', 'type'])
            : collect();
        $programs = $canManage
            ? TrainingProgram::query()->published()->where('is_demo', false)->whereHas('center', fn ($query) => $query->where('is_active', true)->where('is_demo', false)->whereIn('type', ['PTC', 'PSAT']))
                ->when($user->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $user->training_center_id))
                ->with('center:id,name,type')->orderBy('title')->get(['id', 'title', 'training_center_id'])
            : collect();
        $privateCenters = TrainingCenter::query()->where('is_active', true)->where('is_demo', false)
            ->where('type', 'like', '%private%')->with(['programs' => fn ($query) => $query->published()->select(['id', 'training_center_id', 'title'])])
            ->orderBy('name')->limit(10)->get(['id', 'name', 'type', 'address']);
        $trainerCenterIds = $user->hasRole(Role::TesdaAdmin)
            ? $user->teachingAssignments()->with('program:id,training_center_id')->get()
                ->pluck('program.training_center_id')->filter()->unique()->values()->all()
            : [];

        $scholarships->getCollection()->each(function (Scholarship $scholarship) use ($user, $trainerCenterIds): void {
            $isTesda = $user->hasRole(Role::TesdaAdmin);
            $occupiedSlots = (int) ($scholarship->occupied_slots ?? 0);
            $scholarship->setAttribute('available_slots', $scholarship->slot_limit === null ? null : max(0, $scholarship->slot_limit - $occupiedSlots));
            $scholarship->setAttribute('is_full', $scholarship->slot_limit !== null && $occupiedSlots >= $scholarship->slot_limit);
            $scholarship->setAttribute(
                'can_approve_center',
                $isTesda
                    && $scholarship->status === 'review'
                    && $scholarship->center_approval_status === 'pending'
                    && $user->training_center_id === $scholarship->training_center_id
                    && $scholarship->trainer_approved_by !== $user->id,
            );
            $scholarship->setAttribute(
                'can_approve_trainer',
                $isTesda
                    && $scholarship->status === 'review'
                    && $scholarship->center_approval_status === 'approved'
                    && $scholarship->trainer_approval_status === 'pending'
                    && in_array($scholarship->training_center_id, $trainerCenterIds, true)
                    && $scholarship->center_approved_by !== $user->id,
            );
        });

        $applications->each(function (ScholarshipApplication $application) use ($user, $trainerCenterIds): void {
            $isTesda = $user->hasRole(Role::TesdaAdmin);
            $application->setAttribute(
                'can_approve_center',
                $isTesda
                    && in_array($application->status, ['submitted', 'reviewing'], true)
                    && $application->center_approval_status === 'pending'
                    && $user->training_center_id === $application->scholarship->training_center_id
                    && $application->trainer_approved_by !== $user->id,
            );
            $application->setAttribute(
                'can_approve_trainer',
                $isTesda
                    && in_array($application->status, ['submitted', 'reviewing'], true)
                    && $application->trainer_approval_status === 'pending'
                    && in_array($application->scholarship->training_center_id, $trainerCenterIds, true)
                    && $application->center_approved_by !== $user->id,
            );
            $application->setAttribute(
                'can_override_final',
                $user->hasRole(Role::Admin, Role::TesdaAdmin)
                    && $application->status !== 'cancelled'
                    && (! $user->isCenterScopedTesda() || $user->training_center_id === $application->scholarship->training_center_id),
            );
        });

        if ($request->routeIs('mobile.*')) {
            $routeName = 'mobile.scholarship-applications.documents';
            $myApplications = $this->mobileApplications($myApplications, $routeName);
            $applications = $this->mobileApplications($applications, $routeName);

            return response()->json(compact('scholarships', 'canManage', 'myApplications', 'applications', 'centers', 'programs', 'privateCenters'));
        }

        return view('operations.scholarships', compact('scholarships', 'canManage', 'myApplications', 'applications', 'centers', 'programs', 'privateCenters'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['status'] = in_array($data['status'], ['review', 'published'], true) ? 'review' : $data['status'];
        $data += $this->approvalFieldsForSubmission($data['status']);
        $data['verified_at'] = null;
        $data['published_by'] = null;
        Scholarship::create($data);
        WorkspaceChanged::dispatch('scholarships');

        $message = $data['status'] === 'review'
            ? 'Scholarship sent for TESDA center and trainer approval.'
            : 'Scholarship saved as a draft.';

        return $request->routeIs('mobile.*') ? response()->json(['message' => $message], 201) : back()->with('status', $message);
    }

    public function update(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        $this->authorizeScholarshipAccess($request, $scholarship);

        $data = $this->validatedData($request, $scholarship);
        $data['status'] = in_array($data['status'], ['review', 'published'], true) ? 'review' : $data['status'];
        if ($data['status'] === 'review') {
            $data += $this->approvalFieldsForSubmission('review');
            $data['verified_at'] = null;
            $data['published_by'] = null;
        } elseif ($data['status'] === 'draft') {
            $data['verified_at'] = null;
            $data['published_by'] = null;
        } else {
            $data['verified_at'] = $scholarship->verified_at;
            $data['published_by'] = $scholarship->published_by;
        }
        $scholarship->update($data);
        WorkspaceChanged::dispatch('scholarships');

        $message = $data['status'] === 'review'
            ? 'Scholarship resubmitted for TESDA center and trainer approval.'
            : 'Scholarship updated.';

        return $request->routeIs('mobile.*') ? response()->json(['message' => $message]) : back()->with('status', $message);
    }

    public function destroy(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        $this->authorizeScholarshipAccess($request, $scholarship);

        if ($scholarship->applications()->exists()) {
            $message = 'This scholarship has application history and cannot be deleted. Close it instead to preserve applicant records.';

            return $request->routeIs('mobile.*')
                ? response()->json(['message' => $message], 409)
                : back()->withErrors(['scholarship' => $message]);
        }

        $scholarship->delete();
        WorkspaceChanged::dispatch('scholarships');

        $message = 'Scholarship deleted.';

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }

    public function approve(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::TesdaAdmin), 403);

        $data = $request->validate([
            'stage' => ['required', Rule::in(['center', 'trainer'])],
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($data, $scholarship, $user): void {
            $lockedScholarship = Scholarship::query()->lockForUpdate()->findOrFail($scholarship->id);
            abort_unless($lockedScholarship->status === 'review', 422, 'This scholarship is not awaiting approval.');

            $stage = $data['stage'];
            $otherStage = $stage === 'center' ? 'trainer' : 'center';
            abort_unless($lockedScholarship->{$stage.'_approval_status'} === 'pending', 422, 'This approval stage is already completed.');
            abort_if($lockedScholarship->{$otherStage.'_approved_by'} === $user->id, 403, 'A different TESDA staff member must complete the other approval.');

            if ($stage === 'center') {
                abort_unless($user->training_center_id === $lockedScholarship->training_center_id, 403);
            } else {
                abort_unless($lockedScholarship->center_approval_status === 'approved', 422, 'The training center must approve the listing first.');
                $isAssignedTrainer = $user->teachingAssignments()
                    ->whereHas('program', fn ($query) => $query->where('training_center_id', $lockedScholarship->training_center_id))
                    ->exists();
                abort_unless($isAssignedTrainer, 403);
            }

            $lockedScholarship->update([
                $stage.'_approval_status' => $data['decision'],
                $stage.'_approved_by' => $user->id,
                $stage.'_approved_at' => now(),
                $stage.'_approval_notes' => $data['notes'] ?? null,
                'status' => $data['decision'] === 'rejected' ? 'draft' : $lockedScholarship->status,
                'verified_at' => $data['decision'] === 'rejected' ? null : $lockedScholarship->verified_at,
                'published_by' => $data['decision'] === 'rejected' ? null : $lockedScholarship->published_by,
            ]);

            $lockedScholarship->refresh();
            if ($data['decision'] === 'approved'
                && $lockedScholarship->center_approval_status === 'approved'
                && $lockedScholarship->trainer_approval_status === 'approved') {
                $lockedScholarship->update([
                    'status' => 'published',
                    'verified_at' => now(),
                    'published_by' => $user->id,
                ]);
            }
        });

        WorkspaceChanged::dispatch('scholarships');
        $message = $data['decision'] === 'approved'
            ? 'TESDA '.$data['stage'].' approval saved. The scholarship is public after both approvals.'
            : 'Scholarship declined and returned to drafts.';

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }

    private function validatedData(Request $request, ?Scholarship $scholarship = null): array
    {
        abort_unless($request->user()->hasRole(Role::Admin, Role::TesdaAdmin), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'eligibility' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'assessment_type' => ['nullable', 'string', 'max:100'],
            'assessment_details' => ['nullable', 'string', 'max:5000'],
            'source_url' => [
                in_array($request->input('status'), ['review', 'published'], true) ? 'required' : 'nullable',
                'url:http,https',
                'max:255',
            ],
            'application_url' => ['nullable', 'url:http,https', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'training_program_id' => [
                Rule::requiredIf(in_array($request->input('status'), ['review', 'published'], true)),
                'nullable',
                Rule::exists('training_programs', 'id')->where('status', 'published')->where('is_demo', false),
            ],
            'slot_limit' => [
                Rule::requiredIf(in_array($request->input('status'), ['review', 'published'], true)),
                'nullable',
                'integer',
                'min:1',
                'max:500',
            ],
            'status' => ['required', Rule::in(['draft', 'review', 'published', 'closed'])],
            'training_center_id' => [
                Rule::requiredIf(in_array($request->input('status'), ['review', 'published'], true)),
                'nullable',
                Rule::exists('training_centers', 'id')->where('is_active', true),
            ],
        ]);

        if ($request->user()->isCenterScopedTesda()) {
            $data['training_center_id'] ??= $scholarship?->training_center_id ?? $request->user()->training_center_id;
            abort_unless((int) $data['training_center_id'] === $request->user()->training_center_id, 403);
        }

        if ($scholarship && isset($data['slot_limit'])) {
            $reservedSlots = $scholarship->applications()
                ->whereIn('status', ['submitted', 'reviewing', 'approved'])
                ->count();
            abort_if($data['slot_limit'] < $reservedSlots, 422, 'Available slots cannot be set below the number of applications already holding a place.');
        }

        if (isset($data['training_program_id'])) {
            $program = TrainingProgram::query()->published()->where('is_demo', false)->with('center:id,type')->findOrFail($data['training_program_id']);
            abort_unless(
                (int) $program->training_center_id === (int) ($data['training_center_id'] ?? null)
                    && in_array($program->center?->type, ['PTC', 'PSAT'], true),
                422,
                'Choose a published course offered by the selected PTC or PSAT.',
            );
        }

        return $data;
    }

    private function authorizeScholarshipAccess(Request $request, Scholarship $scholarship): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        abort_unless(! $user->isCenterScopedTesda() || $scholarship->training_center_id === $user->training_center_id, 403);
    }

    /** @return array<string, mixed> */
    private function approvalFieldsForSubmission(string $status): array
    {
        if ($status !== 'review') {
            return [];
        }

        return [
            'center_approval_status' => 'pending',
            'center_approved_by' => null,
            'center_approved_at' => null,
            'center_approval_notes' => null,
            'trainer_approval_status' => 'pending',
            'trainer_approved_by' => null,
            'trainer_approved_at' => null,
            'trainer_approval_notes' => null,
            'submitted_for_approval_at' => now(),
        ];
    }

    private function mobileApplications(Collection $applications, string $routeName): array
    {
        return $applications->map(fn (ScholarshipApplication $application): array => [
            'id' => $application->id,
            'status' => $application->status,
            'created_at' => $application->created_at?->toISOString(),
            'center_approval_status' => $application->center_approval_status,
            'center_approval_notes' => $application->center_approval_notes,
            'center_approved_at' => $application->center_approved_at?->toISOString(),
            'center_approver' => $application->centerApprover,
            'trainer_approval_status' => $application->trainer_approval_status,
            'trainer_approval_notes' => $application->trainer_approval_notes,
            'trainer_approved_at' => $application->trainer_approved_at?->toISOString(),
            'trainer_approver' => $application->trainerApprover,
            'cancelled_at' => $application->cancelled_at?->toISOString(),
            'can_approve_center' => (bool) $application->can_approve_center,
            'can_approve_trainer' => (bool) $application->can_approve_trainer,
            'application_message' => $application->application_message,
            'review_notes' => $application->review_notes,
            'reviewed_at' => $application->reviewed_at?->toISOString(),
            'reviewer' => $application->reviewer,
            'can_override_final' => (bool) $application->can_override_final,
            'scholarship' => $application->scholarship,
            'user' => $application->relationLoaded('user') ? $application->user : null,
            'documents' => collect($application->documents ?? [])->values()->map(fn (array $file, int $index): array => [
                'name' => $file['name'],
                'download_url' => route($routeName, [$application->id, $index]),
            ])->all(),
        ])->all();
    }
}
