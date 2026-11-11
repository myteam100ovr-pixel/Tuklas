<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobOpportunityController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $canManage = $request->user()->hasRole(Role::SuperAdmin);
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $jobs = JobOpportunity::query()->with('employer')
            ->when($search !== '', fn ($query) => $query->where(fn ($jobs) => $jobs->where('title', 'like', '%'.$search.'%')->orWhere('location', 'like', '%'.$search.'%')->orWhere('employer_name', 'like', '%'.$search.'%')->orWhereHas('employer', fn ($employer) => $employer->where('name', 'like', '%'.$search.'%'))))
            ->when($request->user()->hasRole(Role::Youth), fn ($query) => $query->withExists(['recommendedUsers as recommended_for_me' => fn ($users) => $users->whereKey($request->user()->id)]))
            ->when(! $canManage, fn ($query) => $query->published()->where(fn ($employers) => $employers->where('employer_verified', true)->orWhereHas('employer', fn ($employer) => $employer->where('is_verified', true))))
            ->latest()->paginate(20)->withQueryString();

        return $request->routeIs('mobile.*') ? response()->json(compact('jobs', 'canManage')) : view('operations.jobs', compact('jobs', 'canManage'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['posted_by'] = $request->user()->id;
        $data['employer_id'] = $this->resolveEmployer($data)->id;
        JobOpportunity::create($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job opportunity saved.'], 201) : back()->with('status', 'Job opportunity saved.');
    }

    public function update(Request $request, JobOpportunity $job): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['employer_id'] = $this->resolveEmployer($data)->id;
        $job->update($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job opportunity updated.']) : back()->with('status', 'Job opportunity updated.');
    }

    public function recommend(Request $request, JobOpportunity $job): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::SuperAdmin), 403);
        abort_unless(JobOpportunity::published()->whereKey($job->id)->exists() && ($job->employer_verified || $job->employer?->is_verified), 422);
        $data = $request->validate(['youth_email' => ['required', 'email', 'exists:users,email']]);
        $youth = User::query()->where('email', $data['youth_email'])->where('role', Role::Youth->value)->where('is_active', true)->firstOrFail();
        $job->recommendedUsers()->syncWithoutDetaching([$youth->id => ['recommended_by' => $request->user()->id]]);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job recommended to career seeker.']) : back()->with('status', 'Job recommended to career seeker.');
    }

    private function validatedData(Request $request): array
    {
        abort_unless($request->user()->hasRole(Role::SuperAdmin), 403);

        return $request->validate([
            'employer_name' => ['required', 'string', 'max:255'],
            'employer_verified' => ['required_if:status,published', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'compensation' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'application_url' => ['nullable', 'url:http,https', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:255'],
            'source_checked_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function resolveEmployer(array $data): Employer
    {
        $employer = Employer::query()->firstOrCreate(
            ['name' => $data['employer_name']],
            [
                'location' => $data['location'] ?? null,
                'is_verified' => (bool) ($data['employer_verified'] ?? false),
            ],
        );

        if (($data['employer_verified'] ?? false) && ! $employer->is_verified) {
            $employer->update(['is_verified' => true]);
        }

        return $employer;
    }
}
