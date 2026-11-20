<?php

namespace App\Http\Controllers\Trainer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CenterTrainerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $this->authorizeManager($request);
        $centerId = $user->training_center_id;
        $centers = TrainingCenter::query()->where('is_active', true)
            ->whereHas('programs', fn ($query) => $query->published())
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->whereKey($centerId))
            ->with(['programs' => fn ($query) => $query->published()->orderBy('title')])
            ->orderBy('name')->get();
        $trainers = User::query()->where('role', Role::TesdaAdmin->value)
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->where(fn ($eligibleUsers) => $eligibleUsers->whereNull('training_center_id')->orWhere('training_center_id', $centerId)))
            ->orderBy('name')->get(['id', 'name', 'role', 'training_center_id']);
        $assignmentPairs = $centers->flatMap(function (TrainingCenter $center) use ($trainers) {
            return $trainers->filter(fn (User $trainer): bool => $trainer->training_center_id === null || $trainer->training_center_id === $center->id)
                ->flatMap(fn (User $trainer) => $center->programs->map(fn (TrainingProgram $program): array => [
                    'user_id' => $trainer->id,
                    'trainer_name' => $trainer->name,
                    'training_program_id' => $program->id,
                    'program_title' => $program->title,
                    'center_name' => $center->name,
                ]));
        })->values();
        $assignments = TrainingAssignment::query()->with(['trainer:id,name,training_center_id', 'program:id,title,training_center_id', 'program.center:id,name'])
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->whereHas('program', fn ($program) => $program->where('training_center_id', $centerId)))
            ->latest()->paginate(20);

        return $request->routeIs('mobile.*')
            ? response()->json(['centers' => $centers, 'trainers' => $trainers, 'assignment_pairs' => $assignmentPairs, 'assignments' => $assignments])
            : view('operations.trainers', compact('centers', 'trainers', 'assignments'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request);
        if ($request->routeIs('mobile.*') && is_string($request->input('assignment_pair'))
            && preg_match('/^(\d+):(\d+)$/D', $request->input('assignment_pair'), $pair) === 1) {
            $request->merge(['user_id' => $pair[1], 'training_program_id' => $pair[2]]);
        }
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', Role::TesdaAdmin->value)->where('is_active', true)],
            'training_program_id' => ['required', Rule::exists('training_programs', 'id')->where('status', 'published')->whereNotNull('training_center_id')],
        ]);
        $trainer = User::findOrFail($data['user_id']);
        $program = TrainingProgram::findOrFail($data['training_program_id']);
        $this->authorizePair($request, $trainer, $program);
        TrainingAssignment::query()->firstOrCreate($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Trainer assigned.'], 201) : back()->with('status', 'Trainer assigned.');
    }

    public function destroy(Request $request, TrainingAssignment $assignment): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request);
        $this->authorizePair($request, $assignment->trainer, $assignment->program);
        $assignment->delete();

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Assignment removed.']) : back()->with('status', 'Assignment removed.');
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        if ($request->user()->isCenterScopedTesda()) {
            abort_unless($request->user()->training_center_id, 403);
        }
    }

    private function authorizePair(Request $request, User $trainer, TrainingProgram $program): void
    {
        if ($program->training_center_id === null || ! $program->center()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['training_program_id' => 'Choose a published program at an active training center.']);
        }

        if ($trainer->isCenterScopedTesda()) {
            if ($trainer->training_center_id !== $program->training_center_id) {
                throw ValidationException::withMessages(['user_id' => 'Choose TESDA staff assigned to the selected training center.']);
            }
        }

        if ($request->user()->isCenterScopedTesda()) {
            abort_unless($program->training_center_id === $request->user()->training_center_id, 403);
        }
    }
}
