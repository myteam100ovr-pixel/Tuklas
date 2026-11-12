<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $canManage = $user->hasRole(Role::Admin, Role::TesdaAdmin);
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $sessions = TrainingSession::query()->with(['program:id,title,status', 'center:id,name,type'])
            ->when($search !== '', fn ($query) => $query->where(fn ($sessions) => $sessions->whereHas('program', fn ($program) => $program->where('title', 'like', '%'.$search.'%'))->orWhereHas('center', fn ($center) => $center->where('name', 'like', '%'.$search.'%'))))
            ->withCount(['applications as accepted_count' => fn ($query) => $query->where('status', 'accepted')])
            ->when($user->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $user->training_center_id))
            ->when(! $canManage, fn ($query) => $query->where('status', 'open')->where('starts_at', '>', now())->whereHas('program', fn ($program) => $program->published())->whereHas('center', fn ($center) => $center->where('is_active', true)))
            ->orderBy('starts_at')->paginate(50)->withQueryString();
        $centers = $canManage ? TrainingCenter::query()->where('is_active', true)->when($user->isCenterScopedTesda(), fn ($query) => $query->whereKey($user->training_center_id))->orderBy('name')->get() : collect();
        $programs = $canManage ? TrainingProgram::published()->when($user->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $user->training_center_id))->orderBy('title')->get(['id', 'title']) : collect();

        return $request->routeIs('mobile.*') ? response()->json(compact('sessions', 'centers', 'programs', 'canManage')) : view('operations.sessions', compact('sessions', 'centers', 'programs', 'canManage'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        TrainingSession::create($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Training session saved.'], 201) : back()->with('status', 'Training session saved.');
    }

    public function update(Request $request, TrainingSession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request, $session);
        $data = $this->validatedData($request);
        $accepted = $session->applications()->where('status', 'accepted')->count();
        abort_if($data['capacity'] < $accepted, 422, 'Capacity is below accepted trainees.');
        abort_if($accepted > 0 && ((int) $data['training_center_id'] !== $session->training_center_id || (int) $data['training_program_id'] !== $session->training_program_id), 422, 'A session with accepted trainees cannot move to another center or program.');
        $session->update($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Training session updated.']) : back()->with('status', 'Training session updated.');
    }

    private function authorizeManager(Request $request, ?TrainingSession $session = null): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        if ($user->isCenterScopedTesda()) {
            abort_unless($user->training_center_id && ($session === null || $session->training_center_id === $user->training_center_id), 403);
        }
    }

    private function validatedData(Request $request): array
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'training_program_id' => ['required', Rule::exists('training_programs', 'id')->where('status', 'published')],
            'training_center_id' => ['required', Rule::exists('training_centers', 'id')->where('is_active', true)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', Rule::in(['open', 'closed'])],
        ]);
        $program = TrainingProgram::findOrFail($data['training_program_id']);
        abort_if($program->training_center_id && $program->training_center_id !== (int) $data['training_center_id'], 422, 'This program belongs to another training center.');
        if ($request->user()->isCenterScopedTesda()) {
            abort_unless((int) $data['training_center_id'] === $request->user()->training_center_id, 403);
            abort_unless($program->training_center_id === $request->user()->training_center_id, 403);
        }

        return $data;
    }
}
