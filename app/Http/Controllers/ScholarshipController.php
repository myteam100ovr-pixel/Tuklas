<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Scholarship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $canManage = $request->user()->hasRole(Role::SuperAdmin, Role::TesdaAdmin);
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $scholarships = Scholarship::query()->when(! $canManage, fn ($query) => $query->published())
            ->when($search !== '', fn ($query) => $query->where(fn ($items) => $items->where('title', 'like', '%'.$search.'%')->orWhere('provider', 'like', '%'.$search.'%')))
            ->latest()->paginate(20)->withQueryString();

        return $request->routeIs('mobile.*') ? response()->json(compact('scholarships', 'canManage')) : view('operations.scholarships', compact('scholarships', 'canManage'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['verified_at'] = $data['status'] === 'published' ? now() : null;
        $data['published_by'] = $request->user()->id;
        Scholarship::create($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Scholarship saved.'], 201) : back()->with('status', 'Scholarship saved.');
    }

    public function update(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['verified_at'] = $data['status'] === 'published' ? now() : null;
        $data['published_by'] = $request->user()->id;
        $scholarship->update($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Scholarship updated.']) : back()->with('status', 'Scholarship updated.');
    }

    private function validatedData(Request $request): array
    {
        abort_unless($request->user()->hasRole(Role::SuperAdmin, Role::TesdaAdmin), 403);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'eligibility' => ['nullable', 'string', 'max:5000'],
            'application_url' => ['nullable', 'url:http,https', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
        ]);
    }
}
