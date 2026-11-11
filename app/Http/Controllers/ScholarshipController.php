<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $myApplications = $request->user()->hasRole(Role::Youth)
            ? ScholarshipApplication::query()->with('scholarship:id,title,provider,is_demo')->where('user_id', $request->user()->id)->latest()->get()
            : collect();
        $applications = $canManage
            ? ScholarshipApplication::query()->with(['scholarship:id,title,provider,is_demo', 'user:id,name,email'])->latest()->limit(100)->get()
            : collect();

        if ($request->routeIs('mobile.*')) {
            $routeName = 'mobile.scholarship-applications.documents';
            $myApplications = $this->mobileApplications($myApplications, $routeName);
            $applications = $this->mobileApplications($applications, $routeName);

            return response()->json(compact('scholarships', 'canManage', 'myApplications', 'applications'));
        }

        return view('operations.scholarships', compact('scholarships', 'canManage', 'myApplications', 'applications'));
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
            'requirements' => ['nullable', 'string', 'max:5000'],
            'assessment_type' => ['nullable', 'string', 'max:100'],
            'assessment_details' => ['nullable', 'string', 'max:5000'],
            'source_url' => [$request->input('status') === 'published' ? 'required' : 'nullable', 'url:http,https', 'max:255'],
            'application_url' => ['nullable', 'url:http,https', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
        ]);
    }

    private function mobileApplications(Collection $applications, string $routeName): array
    {
        return $applications->map(fn (ScholarshipApplication $application): array => [
            'id' => $application->id,
            'status' => $application->status,
            'application_message' => $application->application_message,
            'review_notes' => $application->review_notes,
            'reviewed_at' => $application->reviewed_at?->toISOString(),
            'scholarship' => $application->scholarship,
            'user' => $application->relationLoaded('user') ? $application->user : null,
            'documents' => collect($application->documents ?? [])->values()->map(fn (array $file, int $index): array => [
                'name' => $file['name'],
                'download_url' => route($routeName, [$application->id, $index]),
            ])->all(),
        ])->all();
    }
}
