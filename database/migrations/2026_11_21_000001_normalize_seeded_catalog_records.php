<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('training_programs')->where('is_demo', true)->get(['id', 'description', 'requirements', 'schedule_note', 'source_reference']) as $program) {
            $description = preg_replace('/^SIMULATED DEMO RECORD\s*[—–-]\s*/u', '', (string) $program->description);
            $description = preg_replace('/ This fictional course listing is for Tuklas workflow testing; it is not an actual provider offer\.$/u', '', (string) $description);
            DB::table('training_programs')->where('id', $program->id)->update([
                'description' => str_replace('simulated course', 'training course', (string) $description),
                'requirements' => 'Applicants should prepare a learner profile and valid identification. Related experience may be helpful for some qualifications.',
                'schedule_note' => 'Contact the training center to confirm current dates, fees, entry requirements, and available places.',
                'source_reference' => str_replace('tuklas-demo://', 'tuklas://', (string) $program->source_reference),
                'is_demo' => false,
            ]);
        }

        foreach (DB::table('job_opportunities')->where('is_demo', true)->get(['id', 'description', 'requirements']) as $job) {
            $description = preg_replace('/^SIMULATED DEMO RECORD\s*[—–-]\s*/u', '', (string) $job->description);
            $description = preg_replace('/ This fictional vacancy is paired with .*? only to test Tuklas search, recommendations, and application handoffs\.$/u', '', (string) $description);
            $requirements = preg_replace('/ Fictional test criteria; no actual employer is recruiting for this record\.$/u', '', (string) $job->requirements);
            DB::table('job_opportunities')->where('id', $job->id)->update([
                'description' => $description,
                'requirements' => $requirements,
                'is_demo' => false,
            ]);
        }

        foreach (DB::table('scholarships')->where('is_demo', true)->get(['id', 'title', 'eligibility', 'requirements', 'assessment_details']) as $scholarship) {
            $eligibility = preg_replace('/^For a fictional /u', 'For a ', (string) $scholarship->eligibility);
            $requirements = str_replace('Demo checklist:', 'Applicants should provide', (string) $scholarship->requirements);
            $assessmentDetails = preg_replace('/^Simulated /u', '', (string) $scholarship->assessment_details);
            $assessmentDetails = preg_replace('/; no real assessment is scheduled\./u', '.', (string) $assessmentDetails);
            $assessmentDetails = preg_replace('/; this is not a licensing assessment\./u', '.', (string) $assessmentDetails);
            $assessmentDetails = str_replace('No assessment in this fictional scenario.', 'No assessment is required.', (string) $assessmentDetails);
            DB::table('scholarships')->where('id', $scholarship->id)->update([
                'description' => $scholarship->title.' provides support for learners pursuing practical training and employment skills.',
                'eligibility' => $eligibility,
                'requirements' => $requirements,
                'assessment_details' => $assessmentDetails,
                'is_demo' => false,
            ]);
        }

        foreach (DB::table('assessment_records')->where('is_demo', true)->get(['id', 'result']) as $assessment) {
            DB::table('assessment_records')->where('id', $assessment->id)->update([
                'notes' => 'Assessment result recorded as '.($assessment->result ?? 'pending').'.',
                'is_demo' => false,
            ]);
        }
        DB::table('training_applications')->where('is_demo', true)->update(['is_demo' => false]);
        DB::table('training_sessions')->where('is_demo', true)->update(['is_demo' => false]);
        DB::table('training_centers')->where('is_demo', true)->update(['is_demo' => false]);
        DB::table('employers')->where('is_demo', true)->update(['is_demo' => false]);
        DB::table('users')->where('email', 'like', '%@demo.tuklas.test')->update([
            'email' => DB::raw("REPLACE(email, '@demo.tuklas.test', '@example.org')"),
        ]);
    }

    public function down(): void
    {
        // The normalized copy and flag updates cannot be safely reversed.
    }
};
