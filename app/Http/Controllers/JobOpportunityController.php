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
            ->when($search !== '', fn ($query) => $query->where(fn ($jobs) => $jobs->where('title', 'like', '%'.$search.'%')->orWhere('location', 'like', '%'.$search.'%')->orWhereHas('employer', fn ($employer) => $employer->where('name', 'like', '%'.$search.'%'))))
            ->when($request->user()->hasRole(Role::Youth), fn ($query) => $query->withExists(['recommendedUsers as recommended_for_me' => fn ($users) => $users->whereKey($request->user()->id)]))
            ->when(! $canManage, fn ($query) => $query->published()->whereHas('employer', fn ($employer) => $employer->where('is_verified', true)))
            ->latest()->paginate(20)->withQueryString();
        $employers = $canManage ? Employer::query()->where('is_verified', true)->orderBy('name')->get() : collect();

        return $request->routeIs('mobile.*') ? response()->json(compact('jobs', 'canManage', 'employers')) : view('operations.jobs', compact('jobs', 'canManage', 'employers'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['posted_by'] = $request->user()->id;
        JobOpportunity::create($data);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job opportunity saved.'], 201) : back()->with('status', 'Job opportunity saved.');
    }

    public function update(Request $request, JobOpportunity $job): RedirectResponse|JsonResponse
    {
        $job->update($this->validatedData($request));

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job opportunity updated.']) : back()->with('status', 'Job opportunity updated.');
    }

    public function recommend(Request $request, JobOpportunity $job): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::SuperAdmin), 403);
        abort_unless(JobOpportunity::published()->whereKey($job->id)->exists() && $job->employer->is_verified, 422);
        $data = $request->validate(['youth_email' => ['required', 'email', 'exists:users,email']]);
        $youth = User::query()->where('email', $data['youth_email'])->where('role', Role::Youth->value)->where('is_active', true)->firstOrFail();
        $job->recommendedUsers()->syncWithoutDetaching([$youth->id => ['recommended_by' => $request->user()->id]]);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job recommended to career seeker.']) : back()->with('status', 'Job recommended to career seeker.');
    }

    private function validatedData(Request $request): array
    {
        abort_unless($request->user()->hasRole(Role::SuperAdmin), 403);

        return $request->validate([
            'employer_id' => ['required', Rule::exists('employers', 'id')->where('is_verified', true)],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'application_url' => ['nullable', 'url:http,https', 'max:255'],
            'closes_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
        ]);
    }
}
