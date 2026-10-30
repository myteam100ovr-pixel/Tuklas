<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TrainingApplication;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingApplicationController extends Controller
{
    public function store(Request $request, TrainingProgram $program): RedirectResponse
    {
        abort_unless($program->status === 'published', 404);

        TrainingApplication::query()->updateOrCreate(
            ['training_program_id' => $program->id, 'user_id' => $request->user()->id],
            ['status' => 'pending']
        );

        return redirect()->to(route('tesda.index').'#local-programs')
            ->with('status', 'Application request saved. Confirm enrollment and availability with TESDA Lingayen.');
    }

    public function destroy(Request $request, TrainingProgram $program): RedirectResponse
    {
        $application = TrainingApplication::query()
            ->where('training_program_id', $program->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $application->update(['status' => 'withdrawn']);

        return redirect()->to(route('tesda.index').'#local-programs')->with('status', 'Application request withdrawn.');
    }
}
