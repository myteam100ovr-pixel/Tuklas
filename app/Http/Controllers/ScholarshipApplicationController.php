<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Events\WorkspaceChanged;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipApplicationController extends Controller
{
    public function store(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth), 403);

        $data = $request->validate([
            'application_message' => ['required', 'string', 'min:20', 'max:3000'],
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $application = DB::transaction(function () use ($request, $scholarship, $data): ScholarshipApplication {
            $lockedScholarship = Scholarship::query()->lockForUpdate()->findOrFail($scholarship->id);
            abort_unless(Scholarship::published()->whereKey($lockedScholarship->id)->exists(), 404);
            abort_unless($lockedScholarship->training_center_id && $lockedScholarship->training_program_id && $lockedScholarship->slot_limit, 422, 'This scholarship does not have a confirmed course and slot count yet. Contact the training center.');
            $hasActiveApplication = $lockedScholarship->applications()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['submitted', 'reviewing', 'approved'])
                ->lockForUpdate()
                ->exists();
            if ($hasActiveApplication) {
                throw ValidationException::withMessages([
                    'scholarship' => 'You already have an active application for this scholarship.',
                ]);
            }

            $occupiedSlots = $lockedScholarship->applications()
                ->whereIn('status', ['submitted', 'reviewing', 'approved'])
                ->count();
            abort_if(
                $occupiedSlots >= $lockedScholarship->slot_limit,
                422,
                'This scholarship is fully occupied. Please check the other private training centers listed on this page.',
            );

            $documents = collect($request->file('documents', []))->map(fn ($file): array => [
                'name' => basename($file->getClientOriginalName()),
                'path' => $file->store('scholarship-applications/'.$request->user()->id, 'local'),
            ])->all();

            return ScholarshipApplication::query()->create([
                'scholarship_id' => $lockedScholarship->id,
                'user_id' => $request->user()->id,
                'application_message' => $data['application_message'],
                'documents' => $documents,
                'status' => 'submitted',
                'center_approval_status' => 'pending',
                'trainer_approval_status' => 'pending',
            ]);
        });

        WorkspaceChanged::dispatch('scholarships');

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => 'Application submitted. Your place is reserved in submission order while the TESDA center and trainer review it.', 'application' => $application], 201)
            : back()->with('status', 'Application submitted. Your place is reserved in submission order while the TESDA center and trainer review it.');
    }

    public function update(Request $request, ScholarshipApplication $application): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);

        $data = $request->validate([
            'stage' => ['required', Rule::in(['center', 'trainer', 'final'])],
            'decision' => ['required', Rule::in(['approved', 'failed', 'rejected'])],
            'notes' => [
                Rule::requiredIf(in_array($request->input('decision'), ['failed', 'rejected'], true)),
                'nullable',
                'string',
                'min:5',
                'max:3000',
            ],
        ]);

        DB::transaction(function () use ($application, $data, $user): void {
            $lockedApplication = ScholarshipApplication::query()->lockForUpdate()->findOrFail($application->id);
            $lockedScholarship = Scholarship::query()->lockForUpdate()->findOrFail($lockedApplication->scholarship_id);

            if ($data['stage'] === 'final') {
                abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
                abort_unless(! $user->isCenterScopedTesda() || $user->training_center_id === $lockedScholarship->training_center_id, 403);
                abort_unless($lockedApplication->status !== 'cancelled', 422, 'A canceled application cannot receive a final decision.');

                $decision = $data['decision'] === 'approved' ? 'approved' : 'rejected';
                if ($decision === 'approved') {
                    $this->ensureApplicationIsReadyForApproval($lockedApplication);
                }

                $finalDecision = [
                    'status' => $decision,
                    'review_notes' => $decision === 'rejected' ? $data['notes'] : null,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ];
                foreach (['center', 'trainer'] as $stage) {
                    if ($lockedApplication->{$stage.'_approval_status'} === 'pending') {
                        $finalDecision[$stage.'_approval_status'] = 'cancelled';
                    }
                }
                $lockedApplication->update($finalDecision);

                return;
            }

            abort_unless($user->hasRole(Role::TesdaAdmin), 403);
            $stage = $data['stage'];
            $stageStatus = $lockedApplication->{$stage.'_approval_status'};
            $isFirstDecision = in_array($lockedApplication->status, ['submitted', 'reviewing'], true)
                && $stageStatus === 'pending';
            $isDecisionChange = in_array($lockedApplication->status, ['submitted', 'reviewing', 'approved', 'rejected'], true)
                && in_array($stageStatus, ['approved', 'failed', 'rejected'], true);
            abort_unless($isFirstDecision || $isDecisionChange, 422, 'This application stage cannot be changed.');
            foreach (['center', 'trainer'] as $reviewStage) {
                if ($reviewStage !== $stage) {
                    abort_if($lockedApplication->{$reviewStage.'_approved_by'} === $user->id, 403, 'A different TESDA staff member must decide the other approval stage.');
                }
            }

            if ($stage === 'center') {
                abort_unless($user->training_center_id === $lockedScholarship->training_center_id, 403);
            } else {
                $isAssignedTrainer = $user->teachingAssignments()
                    ->whereHas('program', fn ($query) => $query->where('training_center_id', $lockedScholarship->training_center_id))
                    ->exists();
                abort_unless($isAssignedTrainer, 403);
            }

            $decision = $data['decision'] === 'approved' ? 'approved' : 'failed';
            if ($decision === 'approved') {
                $this->ensureApplicationIsReadyForApproval($lockedApplication);
            }

            $otherStage = $stage === 'center' ? 'trainer' : 'center';
            $notes = $decision === 'failed' ? $data['notes'] : null;
            $otherStatus = $lockedApplication->{$otherStage.'_approval_status'};
            $resultingOtherStatus = $decision === 'approved' && $otherStatus === 'cancelled'
                ? 'pending'
                : $otherStatus;
            $applicationUpdates = [
                $stage.'_approval_status' => $decision,
                $stage.'_approved_by' => $user->id,
                $stage.'_approved_at' => now(),
                $stage.'_approval_notes' => $notes,
                'status' => $decision === 'failed'
                    ? 'rejected'
                    : ($resultingOtherStatus === 'approved'
                        ? 'approved'
                        : (in_array($resultingOtherStatus, ['failed', 'rejected'], true) ? 'rejected' : 'reviewing')),
                'review_notes' => $notes,
                'reviewed_by' => $decision === 'failed' ? $user->id : null,
                'reviewed_at' => $decision === 'failed' ? now() : null,
            ];

            if ($decision === 'approved' && $otherStatus === 'cancelled') {
                $applicationUpdates[$otherStage.'_approval_status'] = 'pending';
                $applicationUpdates[$otherStage.'_approved_by'] = null;
                $applicationUpdates[$otherStage.'_approved_at'] = null;
                $applicationUpdates[$otherStage.'_approval_notes'] = null;
            }

            $lockedApplication->update($applicationUpdates);

            if ($decision === 'failed') {
                if ($otherStatus === 'pending') {
                    $lockedApplication->update([$otherStage.'_approval_status' => 'cancelled']);
                }
            }
        });

        WorkspaceChanged::dispatch('scholarships');
        $message = $data['stage'] === 'final'
            ? ($data['decision'] === 'approved'
                ? 'Final scholarship decision saved as approved.'
                : 'Final scholarship decision saved as declined with the reason recorded.')
            : ($data['decision'] === 'approved'
                ? 'TESDA '.$data['stage'].' approval saved. The application is automatically approved once both center and trainer approvals are complete.'
                : 'Application marked as failed with the review reason recorded.');

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }

    public function cancel(Request $request, ScholarshipApplication $application): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth) && $application->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($application): void {
            $lockedApplication = ScholarshipApplication::query()->lockForUpdate()->findOrFail($application->id);
            abort_unless(in_array($lockedApplication->status, ['submitted', 'reviewing'], true), 422, 'Only a pending application can be canceled.');
            $lockedApplication->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'center_approval_status' => $lockedApplication->center_approval_status === 'pending' ? 'cancelled' : $lockedApplication->center_approval_status,
                'trainer_approval_status' => $lockedApplication->trainer_approval_status === 'pending' ? 'cancelled' : $lockedApplication->trainer_approval_status,
            ]);
        });

        WorkspaceChanged::dispatch('scholarships');

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => 'Scholarship application canceled. The reserved slot is available to the next applicant.'])
            : back()->with('status', 'Scholarship application canceled. The reserved slot is available to the next applicant.');
    }

    public function download(Request $request, ScholarshipApplication $application, int $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless(
            $user->id === $application->user_id || $user->hasRole(Role::Admin, Role::TesdaAdmin),
            403,
        );
        $file = $application->documents[$document] ?? null;
        abort_unless(is_array($file) && isset($file['path'], $file['name']), 404);
        abort_unless(Storage::disk('local')->exists($file['path']), 404);

        $disk = Storage::disk('local');
        $mime = $disk->mimeType($file['path']) ?: 'application/octet-stream';
        $inlineTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $disposition = in_array($mime, $inlineTypes, true) ? 'inline' : 'attachment';

        return $disk->response(
            $file['path'],
            basename($file['name']),
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $disposition,
        );
    }

    private function ensureApplicationIsReadyForApproval(ScholarshipApplication $application): void
    {
        if (! $application->isReadyForApproval()) {
            throw ValidationException::withMessages([
                'documents' => 'The application message must be complete and every uploaded document must be available before accepting.',
            ]);
        }
    }
}
