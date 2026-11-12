<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    public function store(Request $request, TrainingProgram $program, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        if ($request->user()->isCenterScopedTesda()) {
            abort_unless($program->training_center_id === $request->user()->training_center_id, 403);
        }
        abort_unless($program->status === 'published', 404);

        $assignment = $request->user()->teachingAssignments()->firstOrCreate([
            'training_program_id' => $program->id,
        ]);

        if ($assignment->wasRecentlyCreated) {
            $activity->record($request->user(), 'chose to teach '.$program->title.'.');
        }

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Course added to your teaching list.']);
        }

        return redirect()->to(route('tesda.index').'#local-programs')->with('status', 'Course added to your teaching list.');
    }

    public function destroy(Request $request, TrainingProgram $program, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        if ($request->user()->isCenterScopedTesda()) {
            abort_unless($program->training_center_id === $request->user()->training_center_id, 403);
        }
        $assignment = $request->user()->teachingAssignments()
            ->where('training_program_id', $program->id)
            ->firstOrFail();
        $assignment->delete();
        $activity->record($request->user(), 'stopped teaching '.$program->title.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Course removed from your teaching list.']);
        }

        return redirect()->to(route('tesda.index').'#local-programs')->with('status', 'Course removed from your teaching list.');
    }
}
