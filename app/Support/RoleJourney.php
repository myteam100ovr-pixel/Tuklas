<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\AssessmentRecord;
use App\Models\CareerPath;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class RoleJourney
{
    /** @return array{title: string, items: array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}>} */
    public function for(User $user): array
    {
        $items = match ($user->role) {
            Role::Youth => $this->careerSeeker($user),
            Role::TesdaAdmin => $this->tesda($user),
            Role::Admin => $this->admin(),
        };

        return ['title' => 'Your steps', 'items' => $items];
    }

    /** @return array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}> */
    private function careerSeeker(User $user): array
    {
        $profile = ProfileCompletion::for($user);
        $hasScan = $user->documentScans()->where('status', 'done')->exists();
        $hasApplications = $user->trainingApplications()->exists();

        return [
            $this->step('profile', 'Complete your profile', 'Add your education, skills, and interests.', $profile['percent'] === 100, 'profile.show', 'profile'),
            $this->step('assessment', 'Record your skills and interests', 'Answer a few questions to guide career suggestions.', $user->youthProfile?->assessment_preferences !== null, 'youth.assessment.index', 'assessment'),
            $this->step('scan', 'Scan a resume or certificate (optional)', 'Get skills and training suggestions from your document.', $hasScan, 'scanner.index', 'scanner'),
            $this->step('careers', 'Review career suggestions', 'Compare suggested roles with your skills and experience.', $hasScan, 'peso.index', 'peso'),
            $this->step('training', 'Choose TESDA training', 'Browse qualifications and save a training goal.', $user->tesdaChoice()->exists(), 'tesda.index', 'tesda'),
            $this->step('apply', 'Request a place in open training', 'Choose a session with available places.', $hasApplications, 'sessions.index', 'sessions'),
            $this->step('track', 'Track your request and assessment', 'Check decisions, dates, and recorded results.', $hasApplications, 'applications.index', 'applications'),
        ];
    }

    /** @return array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}> */
    private function tesda(User $user): array
    {
        $centerId = $user->isCenterScopedTesda() ? $user->training_center_id : null;
        $centers = TrainingCenter::query()->where('is_active', true)->when($centerId, fn ($query) => $query->whereKey($centerId))->exists();
        $programs = TrainingProgram::query()->when($centerId, fn ($query) => $query->where('training_center_id', $centerId));
        $published = (clone $programs)->where('status', 'published')->exists();

        return [
            $this->step('centers', $centerId ? 'Confirm your training center' : 'Set up training centers', $centerId ? 'Your account is scoped to its assigned center.' : 'Add active centers so programs and sessions can be listed.', $centers, 'centers.index', 'Centers'),
            $this->step('qualifications', 'Select teaching qualifications', 'Keep your TESDA qualifications up to date.', $user->trainerQualifications()->exists(), 'tesda.index', 'Course catalog'),
            $this->step('programs', 'Manage training programs', 'Review details and publish verified programs for Career Seekers.', $published, 'trainer.programs.index', 'My courses'),
            $this->step('trainers', 'Assign TESDA staff to programs', 'Connect staff to published programs at their training center.', (clone $programs)->where('status', 'published')->whereHas('teachingAssignments')->exists(), 'center-trainers.index', 'Assign trainers'),
            $this->step('sessions', 'Publish training dates', 'Set dates, capacity, and whether requests are open.', TrainingSession::query()->when($centerId, fn ($query) => $query->where('training_center_id', $centerId))->where('status', 'open')->exists(), 'sessions.index', 'Training dates'),
            $this->step('requests', 'Review training requests', 'Accept or decline pending requests for your center.', TrainingApplication::query()->where('status', 'pending')->when($centerId, fn ($query) => $query->whereHas('session', fn ($sessions) => $sessions->where('training_center_id', $centerId)))->exists(), 'applications.index', 'Training requests', 'ongoing'),
            $this->step('assessments', 'Record assessments', 'Save assessment dates and results for trainees.', AssessmentRecord::query()->when($centerId, fn ($query) => $query->whereHas('application.session', fn ($sessions) => $sessions->where('training_center_id', $centerId)))->exists(), 'assessments.index', 'Assessment results', 'ongoing'),
            $this->step('scholarships', 'Manage scholarships', 'Publish verified funding opportunities.', Scholarship::query()->exists(), 'scholarships.index', 'Scholarships'),
        ];
    }

    /** @return array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}> */
    private function admin(): array
    {
        return [
            $this->step('staff', 'Set up TESDA accounts', 'Create accounts for staff who manage training information.', User::query()->where('role', Role::TesdaAdmin->value)->exists(), 'admin.users.index', 'Users'),
            $this->step('jobs', 'Publish verified job openings', 'Enter the employer and opening details, then verify the listing before publishing.', JobOpportunity::query()->where('status', 'published')->exists(), 'opportunities.index', 'Jobs'),
            $this->step('recommend', 'Recommend jobs to Career Seekers', 'Send a suitable published opening to an active account.', JobOpportunity::query()->whereHas('recommendedUsers')->exists(), 'opportunities.index', 'Jobs', 'ongoing'),
            $this->step('training', 'Oversee training activity', 'Review requests, sessions, scholarships, and assessment records.', TrainingProgram::query()->exists(), 'sessions.index', 'Sessions', 'ongoing'),
            $this->step('careers', 'Publish career paths', 'Maintain career information available to members.', CareerPath::published()->exists(), 'admin.careers.index', 'Careers'),
        ];
    }

    /** @return array{id: string, title: string, description: string, state: string, href: ?string, action: ?string} */
    private function step(string $id, string $title, string $description, bool $done, ?string $routeName, ?string $action, string $incompleteState = 'todo'): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'state' => $done ? 'done' : $incompleteState,
            'href' => $routeName !== null && Route::has($routeName) ? route($routeName) : null,
            'action' => $action,
        ];
    }
}
