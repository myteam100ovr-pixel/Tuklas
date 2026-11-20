<?php

namespace App\Http\Controllers\Trainer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingApplication;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingApplicationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $query = TrainingApplication::query()->with([
            'program:id,title,training_center_id',
            'program.center:id,name',
            'program.sessions' => fn ($sessions) => $sessions
                ->where('status', 'open')
                ->where('starts_at', '>', now())
                ->where('is_demo', false)
                ->withCount(['applications as accepted_count' => fn ($applications) => $applications->where('status', 'accepted')])
                ->with('center:id,name'),
            'session.center:id,name',
            'user:id,name,email',
        ]);
        if ($user->hasRole(Role::Youth)) {
            $query->where('user_id', $user->id);
        } elseif ($user->isCenterScopedTesda()) {
            abort_unless($user->training_center_id, 403);
            $query->where(fn ($applications) => $applications
                ->whereHas('session', fn ($session) => $session->where('training_center_id', $user->training_center_id))
                ->orWhereHas('program', fn ($program) => $program->where('training_center_id', $user->training_center_id)));
        } else {
            abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        }
        $applications = $query->latest()->paginate(50);

        return $request->routeIs('mobile.*') ? response()->json(compact('applications')) : view('operations.applications', compact('applications'));
    }

    public function storeForProgram(Request $request, TrainingProgram $program): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth), 403);

        DB::transaction(function () use ($request, $program): void {
            $program = TrainingProgram::query()->with('center')->lockForUpdate()->findOrFail($program->id);
            abort_unless(
                $program->status === 'published'
                    && ! $program->is_demo
                    && $program->center?->is_active
                    && ! $program->center?->is_demo,
                404,
            );

            $application = TrainingApplication::query()
                ->where('training_program_id', $program->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();
            abort_if($application && in_array($application->status, ['pending', 'accepted'], true), 422, 'You already have an active request for this course.');

            TrainingApplication::query()->updateOrCreate(
                ['training_program_id' => $program->id, 'user_id' => $request->user()->id],
                ['training_session_id' => null, 'status' => 'pending', 'is_demo' => false],
            );
        });

        $message = 'Your course request was sent. The training center will confirm dates and available places before accepting enrollment.';

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => $message], 201)
            : back()->with('status', $message);
    }

    public function store(Request $request, TrainingSession $session): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth), 403);
        DB::transaction(function () use ($request, $session): void {
            $session = TrainingSession::query()->lockForUpdate()->findOrFail($session->id);
            abort_unless($session->status === 'open' && $session->starts_at->isFuture() && $session->program->status === 'published' && $session->center->is_active, 404);
            abort_if($session->applications()->where('status', 'accepted')->count() >= $session->capacity, 422, 'This session is full.');
            $existing = TrainingApplication::query()->where('training_program_id', $session->training_program_id)->where('user_id', $request->user()->id)->first();
            abort_if($existing && in_array($existing->status, ['pending', 'accepted'], true), 422, 'You already have an active application for this program.');
            TrainingApplication::query()->updateOrCreate(
                ['training_program_id' => $session->training_program_id, 'user_id' => $request->user()->id],
                ['training_session_id' => $session->id, 'status' => 'pending'],
            );
        });

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Application submitted.'], 201) : back()->with('status', 'Application submitted.');
    }

    public function update(Request $request, TrainingApplication $application): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        $application->loadMissing(['program:id,training_center_id', 'session:id,training_center_id']);
        if ($user->isCenterScopedTesda()) {
            $applicationCenterId = $application->session?->training_center_id ?? $application->program?->training_center_id;
            abort_unless($user->training_center_id && $applicationCenterId === $user->training_center_id, 403);
        }
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined'])],
            'training_session_id' => ['nullable', 'integer', 'exists:training_sessions,id'],
        ]);
        DB::transaction(function () use ($application, $data, $user): void {
            $lockedApplication = TrainingApplication::query()->lockForUpdate()->findOrFail($application->id);
            abort_unless($lockedApplication->status === 'pending', 422);

            if ($data['status'] === 'declined') {
                $lockedApplication->update(['status' => 'declined']);

                return;
            }

            $sessionId = $data['training_session_id'] ?? $lockedApplication->training_session_id;
            abort_unless($sessionId, 422, 'Add an open training date before accepting this course request.');
            $session = TrainingSession::query()->lockForUpdate()->with(['program', 'center'])->findOrFail($sessionId);
            abort_unless(
                $session->training_program_id === $lockedApplication->training_program_id
                    && $session->status === 'open'
                    && $session->starts_at->isFuture()
                    && ! $session->is_demo
                    && $session->program?->status === 'published'
                    && ! $session->program?->is_demo
                    && $session->center?->is_active
                    && ! $session->center?->is_demo,
                422,
                'Choose an open future session for this course.',
            );
            abort_unless(
                ! $user->isCenterScopedTesda() || $session->training_center_id === $user->training_center_id,
                403,
            );

            $acceptedCount = $session->applications()
                ->where('status', 'accepted')
                ->where('id', '<>', $lockedApplication->id)
                ->count();
            abort_if($acceptedCount >= $session->capacity, 422, 'This session is full.');

            $lockedApplication->update([
                'training_session_id' => $session->id,
                'status' => 'accepted',
            ]);
        });

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Application updated.']) : back()->with('status', 'Application updated.');
    }

    public function destroy(Request $request, TrainingApplication $application): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth) && $application->user_id === $request->user()->id, 403);
        abort_unless($application->status === 'pending', 422);

        $application->update(['status' => 'withdrawn']);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Application withdrawn.']) : back()->with('status', 'Application withdrawn.');
    }
}
