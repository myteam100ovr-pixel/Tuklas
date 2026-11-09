<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
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
                ->with('creator:id,name')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderBy('title')
                ->paginate(12)
                ->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.trainings.form', ['program' => new TrainingProgram]);
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

    public function edit(TrainingProgram $training): View
    {
        return view('admin.trainings.form', ['program' => $training]);
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
        ]);
    }
}
