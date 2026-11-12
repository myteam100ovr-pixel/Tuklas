<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AssessmentRecord;
use App\Models\TrainingApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocalWorkflowDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Local workflow data can only be seeded outside production.');
        }

        $admin = User::query()->where('role', Role::Admin->value)->oldest('id')->first();
        $careerSeeker = User::query()
            ->where('email', config('tuklas.demo_accounts.career_seeker_email'))
            ->where('role', Role::Youth->value)
            ->first();

        if ($admin === null || $careerSeeker === null) {
            throw new RuntimeException('Create the local Admin and Career Seeker demo accounts before seeding workflow data.');
        }

        $this->completeCareerSeekerProfile($careerSeeker);
        $this->seedTrainingActivity($careerSeeker, $admin);

        $this->command?->info('Completed the local Career Seeker profile and added three training requests with two assessment records.');
    }

    private function completeCareerSeekerProfile(User $careerSeeker): void
    {
        if ($careerSeeker->date_of_birth === null) {
            $careerSeeker->forceFill(['date_of_birth' => today()->subYears(22)->toDateString()])->save();
        }

        $profile = $careerSeeker->youthProfile()->firstOrNew();
        $values = [
            'barangay' => 'Poblacion',
            'contact_number' => '09000000000',
            'educational_attainment' => 'Senior high school graduate',
            'employment_status' => 'Student',
            'livelihood_interests' => 'Interested in entry-level IT support, computer maintenance, and helping local businesses use digital tools.',
            'interests' => ['Information technology', 'Business and finance'],
            'skills' => ['Computer literacy', 'Problem solving', 'Communication', 'Planning and organization'],
            'assessment_preferences' => [
                'priority' => 'Find my first job',
                'career_goal' => 'Build experience in IT support while continuing practical training in computer systems servicing.',
                'experience_example' => 'Practiced organizing files, checking basic computer settings, and explaining simple steps to classmates.',
                'desired_skills' => 'Hardware troubleshooting, network basics, and customer support.',
                'learning_mode' => 'Blended online and in-person',
                'schedule' => 'Weekdays',
                'weekly_hours' => '5–10 hours',
                'work_environment' => 'Office or digital work',
                'support_needs' => ['Nearby training providers', 'Scholarship information'],
            ],
        ];

        foreach ($values as $key => $value) {
            if (blank($profile->getAttribute($key))) {
                $profile->setAttribute($key, $value);
            }
        }

        $profile->save();
    }

    private function seedTrainingActivity(User $careerSeeker, User $admin): void
    {
        $representative = User::query()
            ->where('role', Role::CenterRepresentative->value)
            ->whereNotNull('training_center_id')
            ->oldest('id')
            ->first();
        $center = $representative?->trainingCenter
            ?? TrainingCenter::query()->where('is_demo', true)->oldest('id')->first();

        if ($center === null) {
            throw new RuntimeException('Seed the local training catalog before seeding workflow data.');
        }

        $programs = TrainingProgram::query()
            ->published()
            ->where('training_center_id', $center->id)
            ->orderBy('title')
            ->limit(3)
            ->get();

        if ($programs->count() < 3) {
            throw new RuntimeException('The selected local training center needs three published programs.');
        }

        $statuses = ['pending', 'accepted', 'accepted'];
        $applications = [];

        foreach ($programs as $index => $program) {
            $startsAt = $index === 2
                ? today()->subDays(30)->setTime(9, 0)
                : today()->addDays(7 + $index)->setTime(9, 0);
            $session = TrainingSession::query()->firstOrCreate(
                [
                    'training_program_id' => $program->id,
                    'training_center_id' => $center->id,
                    'starts_at' => $startsAt,
                    'is_demo' => true,
                ],
                [
                    'ends_at' => $startsAt->copy()->addHours(6),
                    'capacity' => 20,
                    'status' => $index === 2 ? 'closed' : 'open',
                    'created_by' => $admin->id,
                ],
            );

            $application = TrainingApplication::query()->firstOrNew([
                'training_program_id' => $program->id,
                'user_id' => $careerSeeker->id,
            ]);
            $application->fill([
                'training_session_id' => $session->id,
                'status' => $statuses[$index],
                'is_demo' => true,
            ])->save();
            $applications[] = $application;
        }

        $assessments = [
            [
                'scheduled_at' => now()->addDays(30)->setTime(9, 0),
                'status' => 'scheduled',
                'result' => null,
                'notes' => 'Local test record: assessment is scheduled and awaiting completion.',
            ],
            [
                'scheduled_at' => now()->subDays(2)->setTime(9, 0),
                'status' => 'completed',
                'result' => 'passed',
                'notes' => 'Local test record: sample completed outcome for checking the results workflow.',
            ],
        ];

        foreach (array_slice($applications, 1) as $index => $application) {
            AssessmentRecord::query()->updateOrCreate(
                ['training_application_id' => $application->id],
                $assessments[$index] + ['recorded_by' => $admin->id, 'is_demo' => true],
            );
        }
    }
}
