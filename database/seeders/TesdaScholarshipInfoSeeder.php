<?php

namespace Database\Seeders;

use App\Models\Scholarship;
use Illuminate\Database\Seeder;

class TesdaScholarshipInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scholarship = Scholarship::query()->firstOrNew([
            'title' => 'Private Education Student Financial Assistance (PESFA)',
            'provider' => 'Technical Education and Skills Development Authority (TESDA)',
        ]);

        if (! $scholarship->exists || $scholarship->status === 'draft') {
            $scholarship->fill([
                'description' => 'TESDA describes PESFA as an educational grant for qualified and deserving college freshmen in degree and non-degree courses. The program aims to extend assistance to marginalized students in post-secondary non-degree courses, promote TVET, support a skilled workforce, and assist private institutions. This is program information only, not a confirmed open Pangasinan scholarship: the national page does not confirm local slots, enrollment dates, or an application channel.',
                'eligibility' => 'The official page describes qualified and deserving college freshmen in degree or non-degree courses. Specific eligibility for a local offering is not specified on the linked page.',
                'requirements' => 'Not specified by provider for a current Pangasinan offering. Contact the TESDA Provincial or District Office to confirm requirements and available slots.',
                'assessment_type' => 'Not specified by provider',
                'assessment_details' => 'Not specified by provider for a current local offering.',
                'source_url' => 'https://www.tesda.gov.ph/About/TESDA/1279',
                'application_url' => null,
                'deadline' => null,
                'status' => 'published',
                'verified_at' => '2026-11-11',
                'published_by' => null,
            ])->save();
        }

        $this->command?->info('Published current TESDA PESFA program information with local openings explicitly unconfirmed.');
    }
}
