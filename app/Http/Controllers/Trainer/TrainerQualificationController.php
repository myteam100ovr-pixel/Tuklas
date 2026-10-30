<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\TesdaQualificationCatalog;
use App\Support\ActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainerQualificationController extends Controller
{
    public function store(Request $request, TesdaQualificationCatalog $catalog, ActivityNotifier $activity): RedirectResponse
    {
        $validated = $request->validate(['qualification_id' => ['required', 'string', 'max:30']]);
        $qualification = collect($catalog->all()['qualifications'])
            ->first(fn (array $item): bool => $item['id'] === $validated['qualification_id'] && $item['status'] === 'current');

        if ($qualification === null) {
            return back()->withErrors(['qualification_id' => 'Choose a current TESDA qualification.']);
        }

        $choice = $request->user()->trainerQualifications()->firstOrCreate(
            ['qualification_id' => $qualification['id']],
            ['title' => $qualification['title'], 'nc_level' => $qualification['nc_level']],
        );

        if ($choice->wasRecentlyCreated) {
            $activity->record($request->user(), 'chose to teach '.$choice->title.'.');
        }

        return redirect()->to(route('tesda.index').'#nc-offerings')->with('status', 'Qualification added to your teaching list.');
    }

    public function destroy(Request $request, string $qualification, ActivityNotifier $activity): RedirectResponse
    {
        $choice = $request->user()->trainerQualifications()
            ->where('qualification_id', $qualification)
            ->firstOrFail();
        $title = $choice->title;
        $choice->delete();
        $activity->record($request->user(), 'removed '.$title.' from their teaching list.');

        return redirect()->to(route('tesda.index').'#nc-offerings')->with('status', 'Qualification removed from your teaching list.');
    }
}
