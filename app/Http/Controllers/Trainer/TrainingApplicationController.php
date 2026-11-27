<?php

namespace App\Http\Controllers\Trainer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'declined'])],
            'q' => ['nullable', 'string', 'max:100'],
            'center' => ['nullable', 'integer', 'min:1'],
            'course' => ['nullable', 'integer', 'min:1'],
        ]);
        $baseQuery = clone $query;
        $countsByStatus = (clone $baseQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $query->when($filters['status'] ?? null, fn (Builder $applications, string $status) => $applications->where('status', $status));

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(fn (Builder $applications) => $applications
                ->whereHas('user', fn (Builder $trainees) => $trainees->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('program', fn (Builder $programs) => $programs->where('title', 'like', '%'.$search.'%')));
        }

        if (isset($filters['center'])) {
            $centerId = (int) $filters['center'];
            $query->where(fn (Builder $applications) => $applications
                ->whereHas('session', fn (Builder $sessions) => $sessions->where('training_center_id', $centerId))
                ->orWhere(fn (Builder $applications) => $applications
                    ->whereNull('training_session_id')
                    ->whereHas('program', fn (Builder $programs) => $programs->where('training_center_id', $centerId))));
        }

        if (isset($filters['course'])) {
            $query->where('training_program_id', (int) $filters['course']);
        }

        $applications = $query->latest()->paginate(50)->withQueryString();

        if ($request->routeIs('mobile.*')) {
            return response()->json(compact('applications'));
        }

        $applicationCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (int) $countsByStatus->get('pending', 0),
            'accepted' => (int) $countsByStatus->get('accepted', 0),
            'declined' => (int) $countsByStatus->get('declined', 0),
        ];
        $sessionCenterIds = TrainingSession::query()
            ->whereIn('id', (clone $baseQuery)->whereNotNull('training_session_id')->select('training_session_id'))
            ->pluck('training_center_id');
        $programCenterIds = TrainingProgram::query()
            ->whereIn('id', (clone $baseQuery)->whereNull('training_session_id')->select('training_program_id'))
            ->pluck('training_center_id');
        $centers = TrainingCenter::query()
            ->whereIn('id', $sessionCenterIds->merge($programCenterIds)->filter()->unique())
            ->orderBy('name')
            ->get(['id', 'name']);
        $courses = TrainingProgram::query()
            ->whereIn('id', (clone $baseQuery)->select('training_program_id'))
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('operations.applications', compact('applications', 'applicationCounts', 'centers', 'courses'));
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
                ->whereIn('status', ['pending', 'accepted'])
                ->lockForUpdate()
                ->exists();
            if ($application) {
                throw ValidationException::withMessages([
                    'training_program_id' => 'You already have an active request for this course.',
                ]);
            }

            TrainingApplication::query()->create([
                'training_program_id' => $program->id,
                'training_session_id' => null,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'is_demo' => false,
            ]);
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
            TrainingProgram::query()->lockForUpdate()->findOrFail($session->training_program_id);
            $session = TrainingSession::query()->lockForUpdate()->findOrFail($session->id);
            abort_unless($session->status === 'open' && $session->starts_at->isFuture() && $session->program->status === 'published' && $session->center->is_active, 404);
            $hasActiveApplication = TrainingApplication::query()
                ->where('training_program_id', $session->training_program_id)
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->lockForUpdate()
                ->exists();
            if ($hasActiveApplication) {
                throw ValidationException::withMessages([
                    'training_program_id' => 'You already have an active application for this course.',
                ]);
            }
            abort_if($session->applications()->where('status', 'accepted')->count() >= $session->capacity, 422, 'This session is full.');
            TrainingApplication::query()->create([
                'training_program_id' => $session->training_program_id,
                'training_session_id' => $session->id,
                'user_id' => $request->user()->id,
                'status' => 'pending',
            ]);
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
            'decision_reason' => ['nullable', 'required_if:status,declined', 'string', 'min:5', 'max:3000'],
        ]);
        DB::transaction(function () use ($application, $data, $user): void {
            $lockedApplication = TrainingApplication::query()->lockForUpdate()->findOrFail($application->id);
            $isPending = $lockedApplication->status === 'pending';
            $isDecisionEditable = in_array($lockedApplication->status, ['accepted', 'declined'], true);
            if ($lockedApplication->status === 'accepted'
                && $lockedApplication->training_session_id === null
                && $data['status'] === 'accepted'
                && empty($data['training_session_id'])) {
                throw ValidationException::withMessages([
                    'training_session_id' => 'Choose an available intake to assign to this accepted course request.',
                ]);
            }
            $isAssigningIntake = $lockedApplication->status === 'accepted'
                && $lockedApplication->training_session_id === null
                && $data['status'] === 'accepted'
                && ! empty($data['training_session_id']);
            abort_unless($isPending || $isAssigningIntake || $isDecisionEditable, 422);

            if ($data['status'] === 'declined') {
                $lockedApplication->update([
                    'status' => 'declined',
                    'decision_reason' => $data['decision_reason'],
                ]);

                return;
            }

            $sessionId = array_key_exists('training_session_id', $data)
                ? $data['training_session_id']
                : $lockedApplication->training_session_id;
            if (! $sessionId) {
                if ($isPending || $lockedApplication->status === 'declined') {
                    $lockedApplication->update([
                        'training_session_id' => null,
                        'status' => 'accepted',
                        'decision_reason' => null,
                    ]);

                    return;
                }

                throw ValidationException::withMessages([
                    'training_session_id' => 'Choose an available intake to assign to this accepted course request.',
                ]);
            }
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
                'decision_reason' => null,
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
