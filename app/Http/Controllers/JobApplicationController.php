<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\JobApplication;
use App\Models\JobOpportunity;
use App\Models\User;
use App\Notifications\JobHiredNotification;
use App\Notifications\WorkspaceNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobApplicationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403);

        $applications = JobApplication::query()
            ->with(['job:id,title,employer_name', 'user:id,name,email', 'reviewer:id,name'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(compact('applications'));
        }

        return view('operations.job-applications', compact('applications'));
    }

    public function store(Request $request, JobOpportunity $job): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth), 403);
        abort_unless(
            JobOpportunity::published()->whereKey($job->id)->exists()
                && ($job->is_demo || $job->employer_verified || $job->employer?->is_verified),
            404,
        );
        abort_if(
            $job->applications()->where('user_id', $request->user()->id)->exists(),
            422,
            'You have already applied to this job.',
        );

        $data = $request->validate([
            'qualification_summary' => ['required', 'string', 'min:20', 'max:3000'],
            'application_message' => ['nullable', 'string', 'max:3000'],
        ]);

        $job->applications()->create([
            'user_id' => $request->user()->id,
            'qualification_summary' => $data['qualification_summary'],
            'application_message' => $data['application_message'] ?? null,
            'status' => 'submitted',
        ]);

        User::query()->where('role', Role::Admin->value)->where('is_active', true)->each(
            fn (User $admin) => $admin->notify(new WorkspaceNotice(
                'New job application',
                $request->user()->name.' applied for '.$job->title.'.',
                'job_application',
                Role::Youth->value,
                route('admin.job-applications.index'),
            )),
        );

        return back()->with('status', 'Your job application was submitted to PESO.');
    }

    public function update(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403);
        abort_unless(in_array($jobApplication->status, ['submitted', 'reviewing'], true), 422, 'A final decision has already been recorded for this application.');

        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewing', 'hired', 'not_selected'])],
            'decision_reason' => ['nullable', 'required_if:status,not_selected', 'string', 'min:5', 'max:3000'],
        ]);
        $jobApplication->update([
            'status' => $data['status'],
            'decision_reason' => $data['decision_reason'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $applicationStatus = match ($data['status']) {
            'hired' => 'hired',
            'not_selected' => 'not selected',
            default => 'under review',
        };
        $message = 'PESO marked your application for '.$jobApplication->job->title.' as '.$applicationStatus.'.';
        if ($data['status'] === 'not_selected') {
            $message .= ' Feedback: '.$data['decision_reason'];
        }

        $jobApplication->user->notify(new WorkspaceNotice(
            $data['status'] === 'reviewing' ? 'Job application under review' : 'Job application update',
            $message,
            'job_application',
            Role::Admin->value,
            route('opportunities.index'),
        ));

        if ($data['status'] === 'hired') {
            try {
                $jobApplication->user->notify(new JobHiredNotification(
                    $jobApplication->job->title,
                    $jobApplication->job->employer_name ?: $jobApplication->job->employer?->name ?: 'the employer',
                ));
            } catch (\Throwable $exception) {
                Log::warning('Job application hiring email could not be sent.', [
                    'job_application_id' => $jobApplication->id,
                    'user_id' => $jobApplication->user_id,
                    'exception' => $exception,
                ]);
            }
        }

        return back()->with('status', 'Job application updated.');
    }
}
