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
            abort_if(
                $lockedScholarship->applications()->where('user_id', $request->user()->id)->exists(),
                422,
                'You have already applied to this scholarship.',
            );

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
        abort_unless($user->hasRole(Role::TesdaAdmin), 403);

        $data = $request->validate([
            'stage' => ['required', Rule::in(['center', 'trainer'])],
            'decision' => ['required', Rule::in(['approved', 'failed'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($application, $data, $user): void {
            $lockedApplication = ScholarshipApplication::query()->lockForUpdate()->findOrFail($application->id);
            $lockedScholarship = Scholarship::query()->lockForUpdate()->findOrFail($lockedApplication->scholarship_id);
            abort_unless(in_array($lockedApplication->status, ['submitted', 'reviewing'], true), 422, 'This application is no longer awaiting review.');

            $stage = $data['stage'];
            $otherStage = $stage === 'center' ? 'trainer' : 'center';
            abort_unless($lockedApplication->{$stage.'_approval_status'} === 'pending', 422, 'This approval stage is already complete.');
            abort_if($lockedApplication->{$otherStage.'_approved_by'} === $user->id, 403, 'A different TESDA staff member must approve the second stage.');

            if ($stage === 'center') {
                abort_unless($user->training_center_id === $lockedScholarship->training_center_id, 403);
            } else {
                abort_unless($lockedApplication->center_approval_status === 'approved', 422, 'The training center must approve the application first.');
                $isAssignedTrainer = $user->teachingAssignments()
                    ->whereHas('program', fn ($query) => $query->where('training_center_id', $lockedScholarship->training_center_id))
                    ->exists();
                abort_unless($isAssignedTrainer, 403);
            }

            $decision = $data['decision'];
            $lockedApplication->update([
                $stage.'_approval_status' => $decision,
                $stage.'_approved_by' => $user->id,
                $stage.'_approved_at' => now(),
                $stage.'_approval_notes' => $data['notes'] ?? null,
                'status' => $decision === 'failed'
                    ? 'rejected'
                    : ($stage === 'center' ? 'reviewing' : 'approved'),
                'review_notes' => $data['notes'] ?? null,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            if ($decision === 'failed' && $stage === 'center') {
                $lockedApplication->update(['trainer_approval_status' => 'cancelled']);
            }
        });

        WorkspaceChanged::dispatch('scholarships');
        $message = $data['decision'] === 'approved'
            ? 'TESDA '.$data['stage'].' approval saved.'
            : 'Application marked as failed with the review note saved.';

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

        return Storage::disk('local')->download($file['path'], basename($file['name']));
    }
}
