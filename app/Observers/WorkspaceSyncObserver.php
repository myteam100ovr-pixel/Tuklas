<?php

namespace App\Observers;

use App\Events\WorkspaceChanged;
use App\Models\AssessmentRecord;
use App\Models\CareerPath;
use App\Models\Employer;
use App\Models\JobApplication;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\TesdaChoice;
use App\Models\TrainerQualification;
use App\Models\TrainingApplication;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WorkspaceSyncObserver
{
    public function saved(Model $model): void
    {
        $this->broadcastChange($model);
    }

    public function deleted(Model $model): void
    {
        $this->broadcastChange($model);
    }

    public function restored(Model $model): void
    {
        $this->broadcastChange($model);
    }

    private function broadcastChange(Model $model): void
    {
        $resources = [
            AssessmentRecord::class => 'assessments',
            CareerPath::class => 'career-paths',
            Employer::class => 'employers',
            JobApplication::class => 'job-applications',
            JobOpportunity::class => 'jobs',
            Scholarship::class => 'scholarships',
            ScholarshipApplication::class => 'scholarship-applications',
            TrainingApplication::class => 'training-applications',
            TrainingAssignment::class => 'training-assignments',
            TrainingCenter::class => 'training-centers',
            TrainingProgram::class => 'training-programs',
            TrainingSession::class => 'training-sessions',
            TrainerQualification::class => 'trainer-qualifications',
            TesdaChoice::class => 'tesda-choices',
            User::class => 'users',
            YouthProfile::class => 'profiles',
        ];

        $resource = $resources[$model::class] ?? null;

        if ($resource === null) {
            return;
        }

        try {
            WorkspaceChanged::dispatch($resource);
        } catch (\Throwable $exception) {
            Log::warning('Workspace update was saved but could not be broadcast live.', [
                'resource' => $resource,
                'model' => $model::class,
                'exception' => $exception,
            ]);
        }
    }
}
