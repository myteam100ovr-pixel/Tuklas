<?php

namespace App\Http\Controllers\Trainer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingProgramController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $programs = TrainingProgram::query();
        if ($user->hasRole(Role::CenterRepresentative)) {
            abort_unless($user->training_center_id, 403);
            $programs->where('training_center_id', $user->training_center_id);
        } elseif (! $user->hasRole(Role::TesdaAdmin)) {
            $programs->where('created_by', $user->id);
        }
        $data = [
            'programs' => $programs
                ->latest('updated_at')
                ->paginate(12),
            'teachingAssignments' => $request->user()->teachingAssignments()
                ->with('program:id,title,nc_level,status')
                ->latest('updated_at')
                ->get(),
            'trainerQualifications' => $request->user()->trainerQualifications()->orderBy('title')->get(),
            'centers' => $user->hasRole(Role::TesdaAdmin)
                ? TrainingCenter::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('trainer.programs.index', $data);
    }

    public function create(): View
    {
        return view('trainer.programs.form', ['program' => new TrainingProgram, 'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        $data['training_center_id'] = $request->user()->hasRole(Role::CenterRepresentative, Role::Trainer)
            ? $request->user()->training_center_id : ($data['training_center_id'] ?? null);
        abort_if($request->user()->hasRole(Role::CenterRepresentative) && ! $data['training_center_id'], 403);
        abort_if($request->user()->hasRole(Role::CenterRepresentative) && $data['status'] !== 'draft', 403, 'TESDA must publish center programs.');
        $data['last_verified_at'] = $data['status'] === 'published' ? now() : null;

        $program = TrainingProgram::create($data);
        $activity->record($request->user(), 'added course '.$program->title.' as '.$program->status.'.');

        $message = $data['status'] === 'published'
            ? 'Course published in the TESDA catalog.'
            : 'Course saved.';

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('trainer.programs.index')->with('status', $message);
    }

    public function edit(Request $request, TrainingProgram $program): View
    {
        $this->authorizeOwner($request, $program);

        return view('trainer.programs.form', ['program' => $program, 'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, TrainingProgram $program, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $this->authorizeOwner($request, $program);

        $data = $this->validatedData($request);
        $data['training_center_id'] = $request->user()->hasRole(Role::TesdaAdmin)
            ? ($data['training_center_id'] ?? null) : $program->training_center_id;
        abort_if($request->user()->hasRole(Role::CenterRepresentative) && $data['status'] !== $program->status, 403, 'TESDA manages program publication.');
        if ($data['status'] === 'published') {
            $data['last_verified_at'] = now();
        }

        $program->update($data);
        $activity->record($request->user(), 'updated course '.$program->title.' as '.$program->status.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Course updated.']);
        }

        return redirect()->route('trainer.programs.index')->with('status', 'Course updated.');
    }

    private function authorizeOwner(Request $request, TrainingProgram $program): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::TesdaAdmin)
            || ($user->hasRole(Role::CenterRepresentative) && $user->training_center_id && $program->training_center_id === $user->training_center_id)
            || ($user->hasRole(Role::Trainer) && $program->created_by === $user->id), 403);
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'nc_level' => ['nullable', Rule::in(['NC I', 'NC II', 'NC III', 'NC IV'])],
            'description' => ['nullable', 'string', 'max:3000'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'requirements' => ['nullable', 'string', 'max:3000'],
            'schedule_note' => ['nullable', 'string', 'max:1000'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'training_center_id' => ['nullable', Rule::exists('training_centers', 'id')->where('is_active', true)],
        ]);
    }
}
