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
        Scholarship::query()->firstOrCreate(
            [
                'title' => 'Private Education Student Financial Assistance (PESFA)',
                'provider' => 'Technical Education and Skills Development Authority (TESDA)',
            ],
            [
                'description' => 'TESDA describes PESFA as an educational grant for qualified and deserving college freshmen in degree and non-degree courses. TESDA administers the non-degree TVET grants. This is general program information; no current Pangasinan slot or enrollment period is confirmed by this source.',
                'eligibility' => 'The official page describes qualified and deserving college freshmen in degree or non-degree courses. Specific eligibility for a local offering is not specified on the linked page.',
                'requirements' => 'Not specified by provider for a current Pangasinan offering. Contact the TESDA Provincial or District Office to confirm requirements and available slots.',
                'assessment_type' => 'Not specified by provider',
                'assessment_details' => 'Not specified by provider for a current local offering.',
                'source_url' => 'https://www.tesda.gov.ph/About/TESDA/89',
                'application_url' => null,
                'deadline' => null,
                'status' => 'draft',
                'verified_at' => null,
                'published_by' => null,
            ],
        );

        $this->command?->info('Added the source-linked PESFA program record as a draft pending local slot verification.');
    }
}
