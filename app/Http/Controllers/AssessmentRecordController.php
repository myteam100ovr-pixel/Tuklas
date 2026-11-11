<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AssessmentRecord;
use App\Models\TrainingApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssessmentRecordController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $query = AssessmentRecord::query()->with(['application.user:id,name', 'application.program:id,title', 'application.session.center:id,name']);
        if ($user->hasRole(Role::Youth)) {
            $query->whereHas('application', fn ($application) => $application->where('user_id', $user->id));
        } elseif ($user->hasRole(Role::CenterRepresentative)) {
            $query->whereHas('application.session', fn ($session) => $session->where('training_center_id', $user->training_center_id));
        } else {
            abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        }
        $records = $query->latest()->paginate(50);
        $eligibleApplications = collect();
        if ($user->hasRole(Role::Admin, Role::TesdaAdmin, Role::CenterRepresentative)) {
            $eligibleApplications = TrainingApplication::query()->with(['user:id,name', 'program:id,title'])
                ->where('status', 'accepted')
                ->when($user->hasRole(Role::CenterRepresentative), fn ($applications) => $applications->whereHas('session', fn ($session) => $session->where('training_center_id', $user->training_center_id)))
                ->latest()->limit(100)->get();
        }

        return $request->routeIs('mobile.*') ? response()->json(compact('records', 'eligibleApplications')) : view('operations.assessments', compact('records', 'eligibleApplications'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $application = TrainingApplication::query()->with('session')->findOrFail($data['training_application_id']);
        $this->authorizeApplication($request, $application);
        abort_unless($application->status === 'accepted', 422, 'Only accepted trainees can receive an assessment record.');
        $data['recorded_by'] = $request->user()->id;
        AssessmentRecord::create($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Assessment recorded.'], 201) : back()->with('status', 'Assessment recorded.');
    }

    public function update(Request $request, AssessmentRecord $assessment): RedirectResponse|JsonResponse
    {
        $this->authorizeApplication($request, $assessment->application()->with('session')->firstOrFail());
        $data = $this->validatedData($request);
        $application = TrainingApplication::query()->with('session')->findOrFail($data['training_application_id']);
        $this->authorizeApplication($request, $application);
        abort_unless($application->status === 'accepted', 422);
        $assessment->update($data + ['recorded_by' => $request->user()->id]);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Assessment updated.']) : back()->with('status', 'Assessment updated.');
    }

    private function authorizeApplication(Request $request, TrainingApplication $application): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin, Role::CenterRepresentative), 403);
        if ($user->hasRole(Role::CenterRepresentative)) {
            abort_unless($user->training_center_id && $application->session?->training_center_id === $user->training_center_id, 403);
        }
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'training_application_id' => ['required', Rule::exists('training_applications', 'id')],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'result' => ['nullable', Rule::in(['passed', 'failed', 'pending'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
