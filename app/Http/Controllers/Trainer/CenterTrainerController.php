<?php

namespace App\Http\Controllers\Trainer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CenterTrainerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $this->authorizeManager($request);
        $centerId = $user->training_center_id;
        $trainers = User::query()->where('role', Role::Trainer->value)->where('is_active', true)
            ->when($user->hasRole(Role::CenterRepresentative), fn ($query) => $query->where('training_center_id', $centerId))
            ->orderBy('name')->get(['id', 'name', 'training_center_id']);
        $programs = TrainingProgram::query()->published()
            ->when($user->hasRole(Role::CenterRepresentative), fn ($query) => $query->where('training_center_id', $centerId))
            ->orderBy('title')->get(['id', 'title', 'training_center_id']);
        $assignments = TrainingAssignment::query()->with(['trainer:id,name,training_center_id', 'program:id,title,training_center_id'])
            ->when($user->hasRole(Role::CenterRepresentative), fn ($query) => $query->whereHas('program', fn ($program) => $program->where('training_center_id', $centerId)))
            ->latest()->paginate(20);

        return $request->routeIs('mobile.*') ? response()->json(compact('trainers', 'programs', 'assignments')) : view('operations.trainers', compact('trainers', 'programs', 'assignments'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Trainer->value)->where('is_active', true)],
            'training_program_id' => ['required', Rule::exists('training_programs', 'id')->where('status', 'published')],
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
        abort_unless($request->user()->hasRole(Role::Admin, Role::TesdaAdmin, Role::CenterRepresentative), 403);
        if ($request->user()->hasRole(Role::CenterRepresentative)) {
            abort_unless($request->user()->training_center_id, 403);
        }
    }

    private function authorizePair(Request $request, User $trainer, TrainingProgram $program): void
    {
        abort_unless($program->training_center_id && $trainer->training_center_id === $program->training_center_id, 422, 'Trainer and program must belong to the same center.');
        if ($request->user()->hasRole(Role::CenterRepresentative)) {
            abort_unless($program->training_center_id === $request->user()->training_center_id, 403);
        }
    }
}
