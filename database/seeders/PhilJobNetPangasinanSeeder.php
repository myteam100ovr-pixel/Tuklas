<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class PhilJobNetPangasinanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posterId = User::query()
            ->where('role', Role::Admin->value)
            ->oldest('id')
            ->value('id');

        if ($posterId === null) {
            throw new RuntimeException('A PESO administrator is required before importing job listings.');
        }

        $listings = [
            [
                'title' => 'ACCOUNTS OFFICER',
                'employer_name' => 'ABRA DIOCESAN TEACHERS AND EMPLOYEES MULTI-PURPOSE COOPERATIVE',
                'location' => 'City of Alaminos, Pangasinan',
                'compensation' => null,
                'description' => "I. Major functions\n\n1. Prepare collection itineraries; visit clients; collect loan payments; and issue official receipts.\n\n2. Collect savings-account and share-capital deposits and prepare loan billings for members covered by a cooperative agreement.\n\n3. Encode collection summaries, remit cash to the teller, and submit receipts and transaction details to the loan officer.\n\n4. Prepare daily cash and collection-efficiency reports and a daily monitoring report.\n\n5. Help prepare general assemblies, take part in assembly and community activities, maintain workplace cleanliness, and perform related tasks assigned by the immediate supervisor.",
                'requirements' => 'A graduate of BSBA or another business-related course. Work experience is an advantage. Salary is not specified in the PhilJobNet listing.',
                'source_url' => 'https://philjobnet.gov.ph/job-vacancies/job/accounts-officer-1502733',
            ],
            [
                'title' => 'MECHANICAL ENGINEERING TECHNICIAN (GENERAL)',
                'employer_name' => 'WATERMATIC PHILIPPINES CORPORATION',
                'location' => 'Calasiao, Pangasinan',
                'compensation' => '₱25,000.00',
                'description' => 'Project Engineer – Water and Wastewater Treatment Plant Construction. Supervise civil and structural site works, mechanical installation, piping, and electrical, instrumentation, and control works. Check work against approved plans and specifications; prepare site logs, progress reports, look-ahead schedules, and quantity take-offs; coordinate RFIs, shop drawings, material approvals, and as-built drawings; assist with inspections, non-conformance close-out, testing, commissioning, and project documentation; monitor subcontractors and materials; and implement the construction safety and health program.',
                'requirements' => "Bachelor's degree in Civil, Sanitary, Chemical, Mechanical, Environmental Engineering, or a related field; PRC-licensed engineer; 2–4 years of construction site experience, preferably on water or wastewater treatment projects; ability to read civil, mechanical, piping, and electrical drawings; working knowledge of AutoCAD, MS Excel, and MS Project. Testing and commissioning experience and COSH training are advantages. Must be willing to work at project sites in Luzon and elsewhere in the Philippines and, when required, extended hours, weekends, or holidays. The listing says to email a resume to hr@watermatic.ph with the subject “Project Engineer – [Your Name]”.",
                'source_url' => 'https://philjobnet.gov.ph/job-vacancies/job/mechanical-engineering-technician-general-1503469',
            ],
        ];

        foreach ($listings as $listing) {
            $employer = Employer::query()->firstOrCreate(
                ['name' => $listing['employer_name']],
                ['location' => $listing['location'], 'is_verified' => false],
            );

            $employer->update(['is_verified' => true]);
            $job = JobOpportunity::query()->firstOrNew(['source_url' => $listing['source_url']]);

            if (! $job->exists || $job->status === 'draft') {
                $job->fill([
                    'employer_id' => $employer->id,
                    'employer_name' => $listing['employer_name'],
                    'employer_verified' => true,
                    'title' => $listing['title'],
                    'compensation' => $listing['compensation'],
                    'location' => $listing['location'],
                    'description' => $listing['description'],
                    'requirements' => $listing['requirements'],
                    'application_url' => $listing['source_url'],
                    'source_checked_at' => '2026-11-11',
                    'closes_at' => null,
                    'status' => 'published',
                    'posted_by' => $posterId,
                ])->save();
            }
        }

        JobOpportunity::query()
            ->where('source_url', 'https://philjobnet.gov.ph/job-vacancies/0/product%20specialist/0')
            ->where('status', 'draft')
            ->update(['status' => 'closed']);

        $this->command?->info('Imported '.count($listings).' source-checked Pangasinan PhilJobNet openings for public browsing.');
    }
}
