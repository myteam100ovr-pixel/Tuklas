<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    public function store(Request $request, TrainingProgram $program, ActivityNotifier $activity): RedirectResponse
    {
        abort_unless($program->status === 'published', 404);

        $assignment = $request->user()->teachingAssignments()->firstOrCreate([
            'training_program_id' => $program->id,
        ]);

        if ($assignment->wasRecentlyCreated) {
            $activity->record($request->user(), 'chose to teach '.$program->title.'.');
        }

        return redirect()->to(route('tesda.index').'#local-programs')->with('status', 'Course added to your teaching list.');
    }

    public function destroy(Request $request, TrainingProgram $program, ActivityNotifier $activity): RedirectResponse
    {
        $assignment = $request->user()->teachingAssignments()
            ->where('training_program_id', $program->id)
            ->firstOrFail();
        $assignment->delete();
        $activity->record($request->user(), 'stopped teaching '.$program->title.'.');

        return redirect()->to(route('tesda.index').'#local-programs')->with('status', 'Course removed from your teaching list.');
    }
}
