<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use App\Support\ActivityNotifier;
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
        return view('admin.careers.form', ['career' => new CareerPath]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse
    {
        $career = CareerPath::create($this->validatedData($request));
        $activity->record($request->user(), 'created career path '.$career->title.' as '.$career->status.'.');

        return redirect()->route('admin.careers.index')->with('status', 'Career path saved.');
    }

    public function edit(CareerPath $career): View
    {
        return view('admin.careers.form', compact('career'));
    }

    public function update(Request $request, CareerPath $career, ActivityNotifier $activity): RedirectResponse
    {
        $career->update($this->validatedData($request));
        $activity->record($request->user(), 'updated career path '.$career->title.' as '.$career->status.'.');

        return redirect()->route('admin.careers.index')->with('status', 'Career path updated.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'education_level' => ['nullable', 'string', 'max:60'],
            'source_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }
}
