<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingApplication;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;

class AiContextService
{
    /** @return array<string, mixed> */
    public function buildSystemContext(User $user, ?string $query = null): array
    {
        $search = trim((string) $query);
        preg_match_all('/[\pL\pN]{3,}/u', mb_strtolower($search), $matches);
        $searchTerms = collect($matches[0] ?? [])
            ->reject(fn (string $term): bool => in_array($term, ['the', 'and', 'for', 'with', 'from', 'that', 'this', 'what', 'where', 'can', 'you', 'me', 'my', 'near', 'find', 'show', 'give', 'please', 'current', 'latest', 'available', 'job', 'jobs', 'course', 'courses', 'scholarship', 'scholarships', 'training', 'tesda', 'career', 'careers', 'recommend', 'recommendation', 'recommendations'], true))
            ->unique()->take(4)->values()->all();
        $isStaff = $user->hasRole(Role::Admin, Role::TesdaAdmin);
        $isScopedTesda = $user->isCenterScopedTesda();

        $courses = TrainingProgram::query()
            ->published()
            ->where('is_demo', false)
            ->whereHas('center', fn ($center) => $center
                ->whereIn('type', ['PTC', 'PSAT', 'PRIVATE'])
                ->where('is_active', true)
                ->where('is_demo', false))
            ->when($isScopedTesda, fn ($builder) => $builder->where('training_center_id', $user->training_center_id))
            ->when($searchTerms !== [], function ($builder) use ($searchTerms): void {
                $builder->where(function ($matches) use ($searchTerms): void {
                    foreach ($searchTerms as $term) {
                        $matches->orWhere(fn ($fields) => $fields
                            ->where('title', 'like', '%'.$term.'%')
                            ->orWhere('nc_level', 'like', '%'.$term.'%')
                            ->orWhere('description', 'like', '%'.$term.'%')
                            ->orWhereHas('center', fn ($center) => $center->where('name', 'like', '%'.$term.'%')));
                    }
                });
            })
            ->with(['center:id,name,type', 'sessions' => fn ($sessions) => $sessions
                ->where('status', 'open')
                ->where('starts_at', '>', now())
                ->where('is_demo', false)
                ->withCount(['applications as accepted_count' => fn ($applications) => $applications->where('status', 'accepted')])])
            ->orderBy('title')
            ->limit(8)
            ->get(['id', 'title', 'nc_level', 'description', 'requirements', 'training_center_id']);

        $scholarships = Scholarship::query()
            ->published()
            ->where('is_demo', false)
            ->where(fn ($scholarships) => $scholarships
                ->whereNull('training_center_id')
                ->orWhereHas('center', fn ($center) => $center->where('is_active', true)->where('is_demo', false)))
            ->when($isScopedTesda, fn ($builder) => $builder->where('training_center_id', $user->training_center_id))
            ->when($searchTerms !== [], function ($builder) use ($searchTerms): void {
                $builder->where(function ($matches) use ($searchTerms): void {
                    foreach ($searchTerms as $term) {
                        $matches->orWhere(fn ($fields) => $fields
                            ->where('title', 'like', '%'.$term.'%')
                            ->orWhere('provider', 'like', '%'.$term.'%')
                            ->orWhere('eligibility', 'like', '%'.$term.'%')
                            ->orWhereHas('center', fn ($center) => $center->where('name', 'like', '%'.$term.'%'))
                            ->orWhereHas('trainingProgram', fn ($program) => $program->where('title', 'like', '%'.$term.'%')));
                    }
                });
            })
            ->with(['trainingProgram:id,title', 'center:id,name'])
            ->withCount(['applications as occupied_slots' => fn ($applications) => $applications->whereIn('status', ['submitted', 'reviewing', 'approved'])])
            ->orderBy('title')
            ->limit(8)
            ->get(['id', 'title', 'provider', 'requirements', 'eligibility', 'assessment_type', 'assessment_details', 'deadline', 'training_program_id', 'training_center_id', 'slot_limit']);

        $jobs = JobOpportunity::query()
            ->published()
            ->where('is_demo', false)
            ->where(fn ($verified) => $verified
                ->where('employer_verified', true)
                ->orWhereHas('employer', fn ($employer) => $employer->where('is_verified', true)))
            ->when($searchTerms !== [], function ($builder) use ($searchTerms): void {
                $builder->where(function ($matches) use ($searchTerms): void {
                    foreach ($searchTerms as $term) {
                        $matches->orWhere(fn ($fields) => $fields
                            ->where('title', 'like', '%'.$term.'%')
                            ->orWhere('location', 'like', '%'.$term.'%')
                            ->orWhere('employer_name', 'like', '%'.$term.'%')
                            ->orWhere('requirements', 'like', '%'.$term.'%'));
                    }
                });
            })
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'title', 'employer_name', 'location', 'compensation', 'requirements', 'closes_at']);

        $links = $this->staticLinks($user);
        $courseRecords = $courses->map(function (TrainingProgram $course) use (&$links): array {
            $link = $this->recordLink('course', $course->id, $course->title, route('tesda.index', ['q' => $course->title], false), '/courses?q='.rawurlencode($course->title));
            $links[] = $link;
            $openSessions = $course->sessions->map(fn (TrainingSession $session): array => [
                'starts_at' => $session->starts_at?->toIso8601String(),
                'available_slots' => $session->capacity === null ? null : max(0, $session->capacity - (int) $session->accepted_count),
            ])->values()->all();
            $hasUnlimitedSession = collect($openSessions)->contains(fn (array $session): bool => $session['available_slots'] === null);

            return [
                'id' => $course->id,
                'title' => $course->title,
                'nc_level' => $course->nc_level,
                'center_name' => $course->center?->name,
                'requirements' => $course->requirements,
                'duration_days' => $course->duration_days,
                'slots' => $openSessions === [] || $hasUnlimitedSession
                    ? null
                    : array_sum(array_column($openSessions, 'available_slots')),
                'upcoming_open_sessions' => $openSessions,
                'open_session_count' => count($openSessions),
                'web_url' => $link['web_url'],
                'mobile_route' => $link['mobile_route'],
            ];
        })->all();

        $scholarshipRecords = $scholarships->map(function (Scholarship $scholarship) use (&$links): array {
            $link = $this->recordLink('scholarship', $scholarship->id, $scholarship->title, route('scholarships.index', ['q' => $scholarship->title], false), '/scholarships?q='.rawurlencode($scholarship->title));
            $links[] = $link;

            return [
                'id' => $scholarship->id,
                'title' => $scholarship->title,
                'provider' => $scholarship->provider,
                'course_title' => $scholarship->trainingProgram?->title,
                'center_name' => $scholarship->center?->name,
                'requirements' => $scholarship->requirements,
                'eligibility' => $scholarship->eligibility,
                'assessment_to_take' => $scholarship->assessment_type,
                'assessment_details' => $scholarship->assessment_details,
                'deadline' => $scholarship->deadline?->toDateString(),
                'available_slots' => $scholarship->slot_limit === null ? null : max(0, $scholarship->slot_limit - (int) $scholarship->occupied_slots),
                'web_url' => $link['web_url'],
                'mobile_route' => $link['mobile_route'],
            ];
        })->all();

        $jobRecords = $jobs->map(function (JobOpportunity $job) use (&$links): array {
            $link = $this->recordLink('job', $job->id, $job->title, route('opportunities.index', ['q' => $job->title], false), '/jobs?q='.rawurlencode($job->title));
            $links[] = $link;

            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->employer_name,
                'location' => $job->location,
                'compensation' => $job->compensation,
                'requirements' => $job->requirements,
                'closes_at' => $job->closes_at?->toDateString(),
                'web_url' => $link['web_url'],
                'mobile_route' => $link['mobile_route'],
            ];
        })->all();

        $pendingTrainingRequests = null;
        if ($isStaff) {
            $pendingQuery = TrainingApplication::query()
                ->where('status', 'pending')
                ->where('is_demo', false)
                ->whereHas('program', fn ($program) => $program
                    ->where('is_demo', false)
                    ->whereHas('center', fn ($center) => $center->where('is_demo', false)))
                ->where(fn ($applications) => $applications
                    ->whereNull('training_session_id')
                    ->orWhereHas('session', fn ($session) => $session
                        ->where('is_demo', false)
                        ->whereHas('center', fn ($center) => $center->where('is_demo', false))));

            if ($isScopedTesda) {
                $pendingQuery->where(fn ($applications) => $applications
                    ->whereHas('session', fn ($session) => $session->where('training_center_id', $user->training_center_id))
                    ->orWhereHas('program', fn ($program) => $program->where('training_center_id', $user->training_center_id)));
            }

            $pendingCount = (clone $pendingQuery)->count();
            $pendingRecords = (clone $pendingQuery)
                ->with([
                    'program:id,title,training_center_id',
                    'program.center:id,name',
                    'session:id,training_center_id',
                    'session.center:id,name',
                    'user:id,name',
                ])
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(fn (TrainingApplication $application): array => [
                    'id' => $application->id,
                    'trainee' => $application->user?->name,
                    'course' => $application->program?->title,
                    'training_center' => $application->session?->center?->name ?? $application->program?->center?->name,
                    'submitted_at' => $application->created_at?->toIso8601String(),
                ])
                ->all();

            $pendingTrainingRequests = [
                'pending_count' => $pendingCount,
                'requests' => $pendingRecords,
                'has_more' => $pendingCount > count($pendingRecords),
            ];
        }

        $profile = $user->youthProfile;
        $ownApplications = [
            'training' => $user->trainingApplications()
                ->where('is_demo', false)
                ->whereHas('program', fn ($program) => $program->where('is_demo', false))
                ->with(['program:id,title', 'session:id,training_program_id,starts_at'])
                ->latest('id')->limit(5)->get()->map(fn ($application): array => [
                    'id' => $application->id,
                    'course' => $application->program?->title,
                    'status' => $application->status,
                    'decision_reason' => $application->decision_reason,
                    'starts_at' => $application->session?->starts_at?->toIso8601String(),
                ])->all(),
            'scholarships' => $user->scholarshipApplications()
                ->whereHas('scholarship', fn ($scholarship) => $scholarship->where('is_demo', false))
                ->with('scholarship:id,title')
                ->latest('id')->limit(5)->get()->map(fn ($application): array => [
                    'id' => $application->id,
                    'scholarship' => $application->scholarship?->title ?? 'Scholarship',
                    'status' => $application->status,
                    'center_approval' => $application->center_approval_status,
                    'trainer_approval' => $application->trainer_approval_status,
                    'decline_reason' => $application->review_notes,
                ])->all(),
            'jobs' => $user->jobApplications()
                ->whereHas('job', fn ($job) => $job->where('is_demo', false))
                ->with('job:id,title')->latest('id')->limit(5)->get()->map(fn ($application): array => [
                    'id' => $application->id,
                    'job' => $application->job?->title ?? 'Job',
                    'status' => $application->status,
                    'decision_reason' => $application->decision_reason,
                ])->all(),
        ];

        return [
            'user_role' => $user->role->value,
            'user_profile' => $profile ? [
                'display_name' => $user->name,
                'education' => $profile->educational_attainment,
                'interests' => $profile->interests ?? [],
                'skills' => $profile->skills ?? [],
                'credentials' => $profile->credentials ?? [],
                'assessment_skills' => $profile->assessmentSkills() ?? [],
            ] : null,
            'target_requirements' => null,
            'courses' => $courseRecords,
            'scholarships' => $scholarshipRecords,
            'jobs' => $jobRecords,
            'my_applications' => $ownApplications,
            'pending_training_requests' => $pendingTrainingRequests,
            'links' => collect($links)->unique('web_url')->values()->all(),
        ];
    }

    /** @param array<string, mixed> $context
     * @return array{reply: string, recommended_links: array<int, array<string, mixed>>}
     */
    public function resolveChatLinks(string $reply, array $context): array
    {
        $links = collect($context['links'] ?? [])->unique('web_url')->keyBy('web_url');
        $recommended = [];
        $safeReply = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/u', function (array $match) use ($links, &$recommended): string {
            $link = $links->get($match[2]);

            if (! is_array($link)) {
                return $match[1];
            }

            $recommended[$link['id']] = $link;

            return '['.$match[1].']('.$link['web_url'].')';
        }, $reply) ?? $reply;

        return ['reply' => $safeReply, 'recommended_links' => array_values($recommended)];
    }

    /** @return array<int, array<string, mixed>> */
    private function staticLinks(User $user): array
    {
        $links = [
            $this->recordLink('dashboard', 'home', 'Dashboard', route($user->role->homeRoute(), [], false), '/dashboard'),
            $this->recordLink('courses', 'catalog', 'Browse All Courses', route('tesda.index', [], false), '/courses'),
            $this->recordLink('scholarships', 'catalog', 'View All Scholarships', route('scholarships.index', [], false), '/scholarships'),
            $this->recordLink('jobs', 'catalog', 'Browse All Jobs', route('opportunities.index', [], false), '/jobs'),
            $this->recordLink('profile', 'account', 'Profile', route('profile.show', [], false), '/profile'),
        ];

        if ($user->hasRole(Role::Youth)) {
            $links[] = $this->recordLink('applications', 'mine', 'Track My Training Applications', route('applications.index', [], false), '/applications');
            $links[] = $this->recordLink('assessment_results', 'mine', 'View Assessment Results', route('assessments.index', [], false), '/assessment-results');
        }

        if ($user->hasRole(Role::Admin, Role::TesdaAdmin)) {
            $links[] = $this->recordLink('training_requests', 'queue', 'Review Training Requests', route('applications.index', [], false), '/training-requests');
            $links[] = $this->recordLink('assessment_results', 'queue', 'Review Assessment Results', route('assessments.index', [], false), '/assessment-results');
        }

        if ($user->hasRole(Role::Admin)) {
            $links[] = $this->recordLink('job_applications', 'queue', 'Review Job Applications', route('admin.job-applications.index', [], false), '/job-applications');
        }

        if ($user->hasRole(Role::Admin, Role::Youth)) {
            $links[] = $this->recordLink('scanner', 'tool', 'Document Scanner', route('scanner.index', [], false), '/scanner');
        }

        return $links;
    }

    /** @return array<string, mixed> */
    private function recordLink(string $type, int|string $recordId, string $label, string $webUrl, string $mobileRoute): array
    {
        return [
            'id' => $type.':'.$recordId,
            'type' => $type,
            'record_id' => $recordId,
            'label' => $label,
            'web_url' => $webUrl,
            'mobile_route' => $mobileRoute,
        ];
    }
}
