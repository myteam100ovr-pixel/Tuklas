<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocalSampleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Sample catalog data can only be loaded in local or testing environments.');
        }

        $adminId = User::query()
            ->where('role', Role::SuperAdmin->value)
            ->oldest('id')
            ->value('id');

        if ($adminId === null) {
            throw new RuntimeException('Create a local PESO super administrator before loading sample catalog data.');
        }

        $centers = collect(range(1, 2))->mapWithKeys(function (int $number): array {
            $center = TrainingCenter::query()->firstOrCreate(
                ['name' => sprintf('[SAMPLE] Pangasinan Training Center %02d', $number)],
                [
                    'type' => $number === 1 ? 'PTC' : 'PSAT',
                    'address' => sprintf('Sample address %02d, Pangasinan (fictional)', $number),
                    'contact_email' => sprintf('sample-center-%02d@example.invalid', $number),
                    'contact_phone' => sprintf('000-000-%04d', $number),
                    'is_active' => true,
                ],
            );

            return [$number => $center];
        });

        $levels = ['NC I', 'NC II', 'NC III', null];
        $durations = [15, 20, 30, 45, 60];

        foreach (range(1, 50) as $number) {
            $centerNumber = ($number % 2) + 1;
            $level = $levels[($number - 1) % count($levels)];

            TrainingProgram::query()->firstOrCreate(
                ['title' => sprintf('[SAMPLE] Qualification %02d', $number)],
                [
                    'training_center_id' => $centers[$centerNumber]->id,
                    'nc_level' => $level,
                    'description' => sprintf('Fictional qualification %02d for testing the training catalog and search.', $number),
                    'duration_days' => $durations[($number - 1) % count($durations)],
                    'requirements' => 'Sample data only: test the requirements field with this fictional entry.',
                    'schedule_note' => 'Sample schedule only. No real training dates, available places, or provider offering are represented.',
                    'source_reference' => 'SAMPLE DATA — generated locally for testing; not a real qualification or provider.',
                    'status' => 'published',
                    'last_verified_at' => null,
                ],
            );
        }

        $jobFamilies = ['Accounts Assistant', 'Customer Service Associate', 'IT Support Technician', 'Office Clerk', 'Welding Assistant'];
        $locations = ['Sample location A, Pangasinan', 'Sample location B, Pangasinan', 'Sample location C, Pangasinan'];

        foreach (range(1, 50) as $number) {
            $employer = Employer::query()->firstOrCreate(
                ['name' => sprintf('[SAMPLE] Employer %02d', $number)],
                [
                    'location' => $locations[($number - 1) % count($locations)],
                    'is_verified' => true,
                ],
            );

            JobOpportunity::query()->firstOrCreate(
                ['title' => sprintf('[SAMPLE] %s %02d', $jobFamilies[($number - 1) % count($jobFamilies)], $number)],
                [
                    'employer_id' => $employer->id,
                    'employer_name' => $employer->name,
                    'employer_verified' => true,
                    'compensation' => 'Sample pay: ₱'.number_format(12000 + ($number * 350), 2).' per month',
                    'location' => $locations[($number - 1) % count($locations)],
                    'description' => sprintf('Fictional job opening %02d. Use this record to test job browsing and search.', $number),
                    'requirements' => 'Sample data only: qualifications and employer details are fictional.',
                    'application_url' => null,
                    'source_url' => null,
                    'source_checked_at' => null,
                    'closes_at' => null,
                    'status' => 'published',
                    'posted_by' => $adminId,
                ],
            );
        }

        $scholarshipTypes = ['Training Support Grant', 'Skills Development Award', 'Career Starter Scholarship', 'Technical Study Grant'];

        foreach (range(1, 50) as $number) {
            Scholarship::query()->firstOrCreate(
                ['title' => sprintf('[SAMPLE] %s %02d', $scholarshipTypes[($number - 1) % count($scholarshipTypes)], $number)],
                [
                    'provider' => sprintf('[SAMPLE] Scholarship Provider %02d', (($number - 1) % 10) + 1),
                    'description' => sprintf('Fictional scholarship record %02d for testing the scholarship catalog.', $number),
                    'eligibility' => 'Sample data only: eligibility terms are fictional.',
                    'requirements' => 'Sample data only: test documents and requirements are fictional.',
                    'assessment_type' => 'Sample assessment',
                    'assessment_details' => 'Fictional assessment details for testing only; this is not a provider requirement.',
                    'source_url' => null,
                    'application_url' => null,
                    'deadline' => null,
                    'status' => 'published',
                    'verified_at' => now(),
                    'published_by' => $adminId,
                ],
            );
        }

        $this->command?->info('Loaded 50 fictional sample records for each catalog in the local database. These records are labeled [SAMPLE] and are not real listings.');
    }
}
