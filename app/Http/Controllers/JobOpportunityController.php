<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\User;
use App\Notifications\JobHiredNotification;
use App\Notifications\WorkspaceNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobOpportunityController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $canManage = $request->user()->hasRole(Role::Admin);
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $jobs = JobOpportunity::query()->with('employer')
            ->when($canManage, fn ($query) => $query->with(['recommendedUsers' => fn ($users) => $users->select('users.id', 'users.name', 'users.email')->orderBy('name')]))
            ->when($request->user()->hasRole(Role::Youth), fn ($query) => $query->with(['recommendedUsers' => fn ($users) => $users->select('users.id', 'users.name', 'users.email')->where('users.id', $request->user()->id)]))
            ->when($search !== '', fn ($query) => $query->where(fn ($jobs) => $jobs->where('title', 'like', '%'.$search.'%')->orWhere('location', 'like', '%'.$search.'%')->orWhere('employer_name', 'like', '%'.$search.'%')->orWhereHas('employer', fn ($employer) => $employer->where('name', 'like', '%'.$search.'%'))))
            ->when($request->user()->hasRole(Role::Youth), fn ($query) => $query->withExists(['recommendedUsers as recommended_for_me' => fn ($users) => $users->where('users.id', $request->user()->id)]))
            ->when(! $canManage, function ($query) use ($request): void {
                $publishedAndVerified = fn ($jobs) => $jobs->published()->where(fn ($employers) => $employers->where('is_demo', true)->orWhere('employer_verified', true)->orWhereHas('employer', fn ($employer) => $employer->where('is_verified', true)));
                if ($request->user()->hasRole(Role::Youth)) {
                    $query->where(fn ($jobs) => $jobs->where($publishedAndVerified)
                        ->orWhereHas('recommendedUsers', fn ($users) => $users->where('users.id', $request->user()->id)->whereNotNull('job_recommendations.hired_at')));
                } else {
                    $publishedAndVerified($query);
                }
            })
            ->latest()->paginate(50)->withQueryString();

        return $request->routeIs('mobile.*') || $request->expectsJson() ? response()->json(compact('jobs', 'canManage')) : view('operations.jobs', compact('jobs', 'canManage'));
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
        abort_unless($request->user()->hasRole(Role::Admin), 403);
        abort_unless(JobOpportunity::published()->whereKey($job->id)->exists() && ($job->is_demo || $job->employer_verified || $job->employer?->is_verified), 422);
        $data = $request->validate(['youth_email' => ['required', 'email', 'exists:users,email']]);
        $youth = User::query()->where('email', $data['youth_email'])->where('role', Role::Youth->value)->where('is_active', true)->firstOrFail();
        $job->recommendedUsers()->syncWithoutDetaching([$youth->id => ['recommended_by' => $request->user()->id]]);

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Job recommended to career seeker.']) : back()->with('status', 'Job recommended to career seeker.');
    }

    public function hire(Request $request, JobOpportunity $job): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403);
        $data = $request->validate(['youth_email' => ['required', 'email', 'exists:users,email']]);
        $youth = User::query()->where('email', $data['youth_email'])->where('role', Role::Youth->value)->where('is_active', true)->firstOrFail();

        $newlyHired = DB::transaction(function () use ($job, $youth, $request): bool {
            $match = $job->recommendedUsers()->whereKey($youth->id)->lockForUpdate()->firstOrFail();
            if ($match->pivot->hired_at !== null || $match->pivot->not_selected_at !== null) {
                return false;
            }

            $job->recommendedUsers()->updateExistingPivot($youth->id, [
                'hired_at' => now(),
                'hired_by' => $request->user()->id,
            ]);

            return true;
        });

        if (! $newlyHired) {
            $message = 'A decision has already been recorded for this career seeker.';

            return $request->routeIs('mobile.*') || $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
        }

        $employerName = $job->employer_name ?: $job->employer?->name ?: 'the employer';
        $youth->notify(new WorkspaceNotice(
            'PESO recorded your hiring',
            'You were marked as hired for '.$job->title.' at '.$employerName.'.',
            'hiring',
            Role::Admin->value,
            route('opportunities.index'),
        ));

        $emailSent = true;
        try {
            $youth->notify(new JobHiredNotification($job->title, $employerName));
        } catch (\Throwable $exception) {
            $emailSent = false;
            Log::warning('Hiring email could not be sent.', ['job_id' => $job->id, 'user_id' => $youth->id, 'exception' => $exception]);
        }

        $message = $emailSent ? 'Hiring recorded and the career seeker notified by email and in the app.' : 'Hiring recorded and the in-app notification sent. The email could not be sent.';

        return $request->routeIs('mobile.*') || $request->expectsJson() ? response()->json(['message' => $message, 'email_sent' => $emailSent]) : back()->with('status', $message);
    }

    public function notSelected(Request $request, JobOpportunity $job): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403);
        $data = $request->validate([
            'youth_email' => ['required', 'email', 'exists:users,email'],
            'reason' => ['required', 'string', 'min:5', 'max:3000'],
        ]);
        $youth = User::query()->where('email', $data['youth_email'])->where('role', Role::Youth->value)->where('is_active', true)->firstOrFail();

        $recorded = DB::transaction(function () use ($job, $youth, $request, $data): bool {
            $match = $job->recommendedUsers()->whereKey($youth->id)->lockForUpdate()->firstOrFail();
            if ($match->pivot->hired_at !== null || $match->pivot->not_selected_at !== null) {
                return false;
            }

            $job->recommendedUsers()->updateExistingPivot($youth->id, [
                'not_selected_at' => now(),
                'not_selected_by' => $request->user()->id,
                'not_selected_reason' => $data['reason'],
            ]);

            return true;
        });

        if (! $recorded) {
            $message = 'A decision has already been recorded for this career seeker.';

            return $request->routeIs('mobile.*') || $request->expectsJson() ? response()->json(['message' => $message], 422) : back()->with('status', $message);
        }

        $youth->notify(new WorkspaceNotice(
            'Application update',
            'You were not selected for '.$job->title.'. PESO feedback: '.$data['reason'],
            'hiring',
            Role::Admin->value,
            route('opportunities.index'),
        ));

        $message = 'Non-selection recorded and the career seeker was notified.';

        return $request->routeIs('mobile.*') || $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }

    private function validatedData(Request $request): array
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403);

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
