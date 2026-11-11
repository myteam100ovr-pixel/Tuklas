<?php

namespace App\Http\Controllers\Trainer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingApplication;
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
        $query = TrainingApplication::query()->with(['program:id,title', 'session.center:id,name', 'user:id,name,email']);
        if ($user->hasRole(Role::Youth)) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole(Role::CenterRepresentative)) {
            $query->whereHas('session', fn ($session) => $session->where('training_center_id', $user->training_center_id));
        } else {
            abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        }
        $applications = $query->latest()->paginate(50);

        return $request->routeIs('mobile.*') ? response()->json(compact('applications')) : view('operations.applications', compact('applications'));
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
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin, Role::CenterRepresentative), 403);
        if ($user->hasRole(Role::CenterRepresentative)) {
            abort_unless($user->training_center_id && $application->session?->training_center_id === $user->training_center_id, 403);
        }
        $data = $request->validate(['status' => ['required', Rule::in(['accepted', 'declined'])]]);
        DB::transaction(function () use ($application, $data): void {
            $session = TrainingSession::query()->lockForUpdate()->findOrFail($application->training_session_id);
            $application->refresh();
            abort_unless($application->status === 'pending', 422);
            abort_if($data['status'] === 'accepted' && $session->applications()->where('status', 'accepted')->count() >= $session->capacity, 422, 'This session is full.');
            $application->update($data);
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
