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
            ->where('role', Role::SuperAdmin->value)
            ->oldest('id')
            ->value('id');

        if ($posterId === null) {
            throw new RuntimeException('A PESO super administrator is required before importing job listings.');
        }

        $listings = [
            [
                'title' => 'ACCOUNTS OFFICER',
                'employer_name' => 'ABRA DIOCESAN TEACHERS AND EMPLOYEES MULTI-PURPOSE COOPERATIVE',
                'location' => 'City of Alaminos, Pangasinan',
                'compensation' => null,
                'description' => 'Prepare collection itineraries, visit clients, collect loan payments, issue receipts, encode collection summaries, prepare daily cash reports, and assist with cooperative assemblies and community activities.',
                'requirements' => 'A graduate of BSBA or any business-related course. Experience is an advantage. PhilJobNet does not specify a salary in the listing.',
                'source_url' => 'https://philjobnet.gov.ph/job-vacancies/job/accounts-officer-1502733',
            ],
            [
                'title' => 'MECHANICAL ENGINEERING TECHNICIAN (GENERAL)',
                'employer_name' => 'WATERMATIC PHILIPPINES CORPORATION',
                'location' => 'Calasiao, Pangasinan',
                'compensation' => '₱25,000.00',
                'description' => 'Support construction, testing, and commissioning of water and wastewater treatment projects. The listed responsibilities include site supervision, inspections, progress reporting, equipment installation, and commissioning support.',
                'requirements' => 'Bachelor’s degree in Civil, Sanitary, Chemical, Mechanical, or Environmental Engineering, or a related field; PRC-licensed engineer; 2–4 years of construction site experience. AutoCAD, MS Excel, MS Project, and COSH training are listed as advantages.',
                'source_url' => 'https://philjobnet.gov.ph/job-vacancies/job/mechanical-engineering-technician-general-1503469',
            ],
            [
                'title' => 'PRODUCT SPECIALIST',
                'employer_name' => 'ASIAWIDE HUMAN RESOURCES AND CONSULTANCY, CORP',
                'location' => 'Mangaldan, Pangasinan',
                'compensation' => '₱755.00 (pay period not specified)',
                'description' => 'PhilJobNet’s listing summary identifies a contractual Product Specialist opening in Mangaldan, Pangasinan, posted November 3, 2026. Review the source listing for the complete job description and confirm the opening before publishing.',
                'requirements' => 'Education level and detailed qualifications were not specified in the retrieved PhilJobNet listing summary. Confirm details on the source listing.',
                'source_url' => 'https://philjobnet.gov.ph/job-vacancies/0/product%20specialist/0',
            ],
        ];

        foreach ($listings as $listing) {
            $employer = Employer::query()->firstOrCreate(
                ['name' => $listing['employer_name']],
                ['location' => $listing['location'], 'is_verified' => false],
            );

            JobOpportunity::query()->firstOrCreate(
                ['source_url' => $listing['source_url']],
                [
                    'employer_id' => $employer->id,
                    'employer_name' => $listing['employer_name'],
                    'employer_verified' => false,
                    'title' => $listing['title'],
                    'compensation' => $listing['compensation'],
                    'location' => $listing['location'],
                    'description' => $listing['description'],
                    'requirements' => $listing['requirements'],
                    'application_url' => $listing['source_url'],
                    'source_checked_at' => '2026-11-11',
                    'closes_at' => null,
                    'status' => 'draft',
                    'posted_by' => $posterId,
                ],
            );
        }

        $this->command?->info('Imported '.count($listings).' source-linked Pangasinan job drafts for PESO review.');
    }
}
