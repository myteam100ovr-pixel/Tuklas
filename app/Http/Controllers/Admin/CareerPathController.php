<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use App\Support\ActivityNotifier;
use App\Support\CareerSkillCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CareerPathController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = in_array($status, ['draft', 'published', 'archived'], true) ? $status : null;

        return view('admin.careers.index', [
            'careers' => CareerPath::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderBy('title')
                ->paginate(12)
                ->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.careers.form', ['career' => new CareerPath, 'skillOptions' => CareerSkillCatalog::OPTIONS]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse
    {
        $career = CareerPath::create($this->validatedData($request));
        $activity->record($request->user(), 'created career path '.$career->title.' as '.$career->status.'.');

        return redirect()->route('admin.careers.index')->with('status', 'Career path saved.');
    }

    public function edit(CareerPath $career): View
    {
        return view('admin.careers.form', ['career' => $career, 'skillOptions' => CareerSkillCatalog::OPTIONS]);
    }

    public function update(Request $request, CareerPath $career, ActivityNotifier $activity): RedirectResponse
    {
        $career->update($this->validatedData($request));
        $activity->record($request->user(), 'updated career path '.$career->title.' as '.$career->status.'.');

        return redirect()->route('admin.careers.index')->with('status', 'Career path updated.');
    }

    public function destroy(Request $request, CareerPath $career, ActivityNotifier $activity): RedirectResponse
    {
        $title = $career->title;
        $career->delete();
        $activity->record($request->user(), 'deleted career path '.$title.'.');

        return redirect()->route('admin.careers.index')->with('status', 'Career path deleted.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $skillRules = $request->input('status') === 'published'
            ? ['required', 'array', 'min:3', 'max:8']
            : ['sometimes', 'nullable', 'array', 'max:8'];
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'education_level' => ['nullable', 'string', 'max:60'],
            'required_skills' => $skillRules,
            'required_skills.*' => ['string', 'distinct', Rule::in(CareerSkillCatalog::OPTIONS)],
            'source_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if ($request->boolean('required_skills_present') && ! array_key_exists('required_skills', $data)) {
            $data['required_skills'] = [];
        }

        return $data;
    }
}
