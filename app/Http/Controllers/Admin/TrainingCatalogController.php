<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = in_array($status, ['draft', 'published', 'archived'], true) ? $status : null;

        return view('admin.trainings.index', [
            'programs' => TrainingProgram::query()
                ->with(['creator:id,name', 'center:id,name'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderBy('title')
                ->paginate(12)
                ->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.trainings.form', [
            'program' => new TrainingProgram,
            'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        $data['last_verified_at'] = $data['status'] === 'published' ? now() : null;
        $program = TrainingProgram::create($data);
        $activity->record($request->user(), 'created course '.$program->title.' as '.$program->status.'.');

        return redirect()->route('admin.trainings.index')->with('status', 'Training program saved.');
    }

    public function storeForCenter(Request $request, TrainingCenter $center, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $this->authorizeCenterAccess($request, $center);
        $data = $this->validatedData($request);
        $data['training_center_id'] = $center->id;
        $data['created_by'] = $request->user()->id;
        $data['last_verified_at'] = $data['status'] === 'published' ? now() : null;
        $program = TrainingProgram::create($data);
        $activity->record($request->user(), 'added course '.$program->title.' to '.$center->name.'.');

        $message = 'Course added to '.$center->name.'.';

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => $message], 201)
            : redirect()->route('centers.index')->with('status', $message);
    }

    public function edit(TrainingProgram $training): View
    {
        return view('admin.trainings.form', [
            'program' => $training,
            'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, TrainingProgram $training, ActivityNotifier $activity): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ($data['status'] === 'published') {
            $data['last_verified_at'] = now();
        }
        $training->update($data);
        $activity->record($request->user(), 'updated course '.$training->title.' as '.$training->status.'.');

        return redirect()->route('admin.trainings.index')->with('status', 'Training program updated.');
    }

    public function destroy(Request $request, TrainingProgram $training, ActivityNotifier $activity): RedirectResponse
    {
        if ($training->hasLinkedRecords()) {
            return redirect()->route('admin.trainings.index')->withErrors([
                'program' => 'This course has applications, schedules, trainer assignments, or scholarships. Archive it instead to preserve those records.',
            ]);
        }

        $title = $training->title;
        $training->delete();
        $activity->record($request->user(), 'deleted course '.$title.'.');

        return redirect()->route('admin.trainings.index')->with('status', 'Training program deleted.');
    }

    public function updateForCenter(Request $request, TrainingCenter $center, TrainingProgram $training, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $this->authorizeCenterAccess($request, $center);
        abort_unless($training->training_center_id === $center->id, 404);

        $data = $this->validatedData($request);
        $data['training_center_id'] = $center->id;
        $data['last_verified_at'] = $data['status'] === 'published' ? now() : null;
        $training->update($data);
        $activity->record($request->user(), 'updated course '.$training->title.' at '.$center->name.'.');

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => 'Course updated.'])
            : redirect()->route('centers.index')->with('status', 'Course updated.');
    }

    public function destroyForCenter(Request $request, TrainingCenter $center, TrainingProgram $training, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $this->authorizeCenterAccess($request, $center);
        abort_unless($training->training_center_id === $center->id, 404);

        if ($training->hasLinkedRecords()) {
            $message = 'This course has applications, schedules, trainer assignments, or scholarships. Archive it instead to preserve those records.';

            return $request->routeIs('mobile.*')
                ? response()->json(['message' => $message], 409)
                : redirect()->route('centers.index')->withErrors(['program' => $message]);
        }

        $title = $training->title;
        $training->delete();
        $activity->record($request->user(), 'deleted course '.$title.' from '.$center->name.'.');

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => 'Course deleted.'])
            : redirect()->route('centers.index')->with('status', 'Course deleted.');
    }

    private function authorizeCenterAccess(Request $request, TrainingCenter $center): void
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        abort_unless(! $user->isCenterScopedTesda() || $user->training_center_id === $center->id, 403);
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'nc_level' => ['nullable', Rule::in(['NC I', 'NC II', 'NC III', 'NC IV'])],
            'description' => ['nullable', 'string', 'max:3000'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'training_center_id' => ['nullable', Rule::exists('training_centers', 'id')],
            'requirements' => ['nullable', 'string', 'max:3000'],
            'schedule_note' => ['nullable', 'string', 'max:1000'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }
}
