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
            Role::TesdaAdmin => $this->tesda(),
            Role::SuperAdmin => $this->superAdmin(),
            Role::CenterRepresentative => $this->centerRepresentative($user),
            Role::Trainer => $this->trainer($user),
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
    private function tesda(): array
    {
        $centers = TrainingCenter::query()->where('is_active', true)->exists();
        $published = TrainingProgram::published()->exists();

        return [
            $this->step('centers', 'Set up training centers', 'Add active centers so programs and sessions can be listed.', $centers, 'centers.index', 'Centers'),
            $this->step('programs', 'Add and publish programs', 'Check program details, then publish them for Career Seekers.', $published, 'trainer.programs.index', 'My programs'),
            $this->step('trainers', 'Assign trainers', 'Connect each trainer to a published program at their center.', TrainingProgram::published()->whereHas('teachingAssignments')->exists(), 'center-trainers.index', 'Trainers'),
            $this->step('sessions', 'Publish training dates', 'Set dates, capacity, and whether requests are open.', TrainingSession::query()->where('status', 'open')->exists(), 'sessions.index', 'Sessions'),
            $this->step('requests', 'Review training requests', 'Accept or decline pending requests.', TrainingApplication::query()->where('status', 'pending')->exists(), 'applications.index', 'Applications', 'ongoing'),
            $this->step('assessments', 'Record assessments', 'Save assessment dates and results for trainees.', AssessmentRecord::query()->exists(), 'assessments.index', 'Assessments', 'ongoing'),
            $this->step('scholarships', 'Manage scholarships', 'Publish verified funding opportunities.', Scholarship::query()->exists(), 'scholarships.index', 'Scholarships'),
        ];
    }

    /** @return array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}> */
    private function superAdmin(): array
    {
        return [
            $this->step('staff', 'Set up staff accounts', 'Create TESDA, trainer, and center representative accounts.', User::query()->whereIn('role', [Role::TesdaAdmin->value, Role::Trainer->value, Role::CenterRepresentative->value])->exists(), 'admin.users.index', 'Users'),
            $this->step('jobs', 'Publish verified job openings', 'Enter the employer and opening details, then verify the listing before publishing.', JobOpportunity::query()->where('status', 'published')->exists(), 'opportunities.index', 'Jobs'),
            $this->step('recommend', 'Recommend jobs to Career Seekers', 'Send a suitable published opening to an active account.', JobOpportunity::query()->whereHas('recommendedUsers')->exists(), 'opportunities.index', 'Jobs', 'ongoing'),
            $this->step('training', 'Oversee training activity', 'Review requests, sessions, scholarships, and assessment records.', TrainingProgram::query()->exists(), 'sessions.index', 'Sessions', 'ongoing'),
            $this->step('careers', 'Publish career paths', 'Maintain career information available to members.', CareerPath::published()->exists(), 'admin.careers.index', 'Careers'),
        ];
    }

    /** @return array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}> */
    private function centerRepresentative(User $user): array
    {
        $centerId = $user->training_center_id;
        $programs = TrainingProgram::query()->where('training_center_id', $centerId);

        return [
            $this->step('center', 'Confirm your center assignment', 'Ask an administrator to assign your account to a center.', $centerId !== null, 'centers.index', 'Centers'),
            $this->step('program_details', 'Add program details', 'Save course information for your assigned center.', $centerId !== null && (clone $programs)->exists(), 'trainer.programs.index', 'My programs'),
            $this->step('publication', 'Wait for TESDA to publish programs', 'TESDA reviews program details and controls publication.', $centerId !== null && (clone $programs)->where('status', 'published')->exists(), null, null, 'waiting'),
            $this->step('trainers', 'Assign trainers', 'Choose trainers and published programs from your center.', $centerId !== null && TrainingProgram::published()->where('training_center_id', $centerId)->whereHas('teachingAssignments')->exists(), 'center-trainers.index', 'Trainers'),
            $this->step('sessions', 'Set training dates', 'Choose a published program, dates, and capacity.', $centerId !== null && TrainingSession::query()->where('training_center_id', $centerId)->exists(), 'sessions.index', 'Sessions'),
            $this->step('requests', 'Review training requests', 'Accept or decline requests for your center.', TrainingApplication::query()->where('status', 'pending')->whereHas('session', fn ($query) => $query->where('training_center_id', $centerId))->exists(), 'applications.index', 'Applications', 'ongoing'),
            $this->step('assessments', 'Record assessments', 'Save dates and results for your center’s trainees.', AssessmentRecord::query()->whereHas('application.session', fn ($query) => $query->where('training_center_id', $centerId))->exists(), 'assessments.index', 'Assessments', 'ongoing'),
        ];
    }

    /** @return array<int, array{id: string, title: string, description: string, state: string, href: ?string, action: ?string}> */
    private function trainer(User $user): array
    {
        return [
            $this->step('qualifications', 'Add your qualifications', 'Record your TESDA credentials and skills.', $user->trainerQualifications()->exists(), 'trainer.programs.index', 'My programs'),
            $this->step('programs', 'Review your permitted programs', 'Open programs linked to your account or center.', TrainingProgram::query()->where('created_by', $user->id)->exists() || $user->teachingAssignments()->exists(), 'trainer.programs.index', 'My programs'),
            $this->step('plans', 'Help Career Seekers choose training', 'Review qualification options and explain provider requirements.', false, 'tesda.index', 'tesda'),
            $this->step('messages', 'Keep in touch with trainees and staff', 'Use messages for training updates and questions.', $user->sentDirectMessages()->exists() || $user->receivedDirectMessages()->exists(), 'messages.index', 'Messages', 'ongoing'),
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
