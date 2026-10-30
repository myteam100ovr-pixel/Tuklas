<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingProgramController extends Controller
{
    public function index(Request $request): View
    {
        return view('trainer.programs.index', [
            'programs' => TrainingProgram::query()
                ->where('created_by', $request->user()->id)
                ->latest('updated_at')
                ->paginate(12),
            'teachingAssignments' => $request->user()->teachingAssignments()
                ->with('program:id,title,nc_level,status')
                ->latest('updated_at')
                ->get(),
            'trainerQualifications' => $request->user()->trainerQualifications()->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('trainer.programs.form', ['program' => new TrainingProgram]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        $data['last_verified_at'] = $data['status'] === 'published' ? now() : null;

        $program = TrainingProgram::create($data);
        $activity->record($request->user(), 'added course '.$program->title.' as '.$program->status.'.');

        $message = $data['status'] === 'published'
            ? 'Course published in the TESDA catalog.'
            : 'Course saved.';

        return redirect()->route('trainer.programs.index')->with('status', $message);
    }

    public function edit(Request $request, TrainingProgram $program): View
    {
        $this->authorizeOwner($request, $program);

        return view('trainer.programs.form', ['program' => $program]);
    }

    public function update(Request $request, TrainingProgram $program, ActivityNotifier $activity): RedirectResponse
    {
        $this->authorizeOwner($request, $program);

        $data = $this->validatedData($request);
        if ($data['status'] === 'published') {
            $data['last_verified_at'] = now();
        }

        $program->update($data);
        $activity->record($request->user(), 'updated course '.$program->title.' as '.$program->status.'.');

        return redirect()->route('trainer.programs.index')->with('status', 'Course updated.');
    }

    private function authorizeOwner(Request $request, TrainingProgram $program): void
    {
        abort_unless($program->created_by === $request->user()->id, 403);
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
