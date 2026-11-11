<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AssessmentRecord;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class FictionalDemoCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = User::query()
            ->where('role', Role::SuperAdmin->value)
            ->oldest('id')
            ->value('id');

        if ($adminId === null) {
            throw new RuntimeException('Create the PESO super administrator before seeding demo catalog records.');
        }

        $centers = [
            ['name' => 'Baybay Norte Skills Institute', 'address' => 'Rizal Avenue, Lingayen, Pangasinan'],
            ['name' => 'Amianan Technical Learning Center', 'address' => 'Quezon Avenue, Dagupan City, Pangasinan'],
            ['name' => 'Pangasinan Coastline Training Hub', 'address' => 'National Road, Alaminos City, Pangasinan'],
            ['name' => 'Balon Community Skills Center', 'address' => 'San Vicente Road, Urdaneta City, Pangasinan'],
            ['name' => 'Umaani Agricultural Training School', 'address' => 'Poblacion, Bayambang, Pangasinan'],
        ];
        $courses = [
            ['Bookkeeping NC III', 'Organize source documents, record business transactions, reconcile accounts, and prepare basic financial reports using a practice company ledger.', 37],
            ['Computer Systems Servicing NC II', 'Practice computer assembly, operating-system installation, network setup, preventive maintenance, and troubleshooting using a supervised lab.', 35],
            ['Cookery NC II', 'Build kitchen safety, mise en place, food preparation, cooking, plating, and sanitation skills through guided kitchen exercises.', 40],
            ['Bread and Pastry Production NC II', 'Practice measuring ingredients, preparing dough and batters, baking, finishing products, and maintaining a clean production area.', 18],
            ['Electrical Installation and Maintenance NC II', 'Work through wiring plans, safe installation methods, circuit testing, maintenance checks, and basic fault finding in a training workshop.', 25],
            ['Shielded Metal Arc Welding NC II', 'Develop safe electrode selection, joint preparation, welding-position practice, visual inspection, and workshop housekeeping skills.', 34],
            ['Organic Agriculture Production NC II', 'Study soil preparation, composting, crop establishment, integrated farm practices, harvest handling, and simple farm records.', 29],
            ['Automotive Servicing NC I', 'Practice workshop safety, vehicle inspection, basic service procedures, tool handling, and recording routine maintenance findings.', 59],
            ['Driving NC II', 'Practice defensive driving, vehicle checks, traffic awareness, route planning, and professional passenger communication in a simulated course.', 15],
            ['Housekeeping NC II', 'Practice room preparation, cleaning procedures, linen handling, guest requests, safe chemical use, and quality checks.', 55],
        ];

        foreach ($centers as $centerIndex => $centerData) {
            $center = TrainingCenter::query()->firstOrCreate(
                ['name' => $centerData['name']],
                ['type' => 'PTC', 'address' => $centerData['address'], 'is_active' => true, 'is_demo' => true],
            );

            foreach ($courses as $courseIndex => [$title, $description, $durationDays]) {
                $sourceReference = 'tuklas-demo://training/'.$centerIndex.'-'.$courseIndex;
                $program = TrainingProgram::query()->firstOrNew([
                    'training_center_id' => $center->id,
                    'source_reference' => $sourceReference,
                ]);
                $program->fill([
                    'title' => $title,
                    'nc_level' => preg_match('/NC (I{1,3}|IV)\b/', $title, $level) ? 'NC '.$level[1] : null,
                    'duration_days' => $durationDays,
                    'description' => 'SIMULATED DEMO RECORD — '.$description.' This fictional course listing is for Tuklas workflow testing; it is not an actual provider offer.',
                    'requirements' => 'Demo scenario: learner profile, government-issued identification, and prior experience where relevant. These are invented test details, not provider requirements.',
                    'schedule_note' => 'Fictional training schedule for testing only. Confirm real dates, fees, requirements, and available places directly with an authorized provider.',
                    'status' => 'published',
                    'last_verified_at' => null,
                    'created_by' => $adminId,
                    'is_demo' => true,
                ])->save();
            }
        }

        $employers = [
            ['name' => 'Amianan Fresh Foods Cooperative', 'location' => 'Dagupan City, Pangasinan'],
            ['name' => 'Lingayen Bay Facilities Services', 'location' => 'Lingayen, Pangasinan'],
            ['name' => 'North Coast Farm Supply Inc.', 'location' => 'Alaminos City, Pangasinan'],
            ['name' => 'Balon Home and Hardware Trading', 'location' => 'Urdaneta City, Pangasinan'],
            ['name' => 'Umaani Harvest Processing Corp.', 'location' => 'Bayambang, Pangasinan'],
        ];
        $roles = [
            ['title' => 'Accounts Assistant', 'pay' => '₱18,000–₱22,000 monthly', 'description' => 'Maintain orderly transaction records, prepare payment summaries, check supporting documents, and assist with monthly account reconciliation.', 'requirements' => 'Business or accounting studies; careful record keeping; spreadsheet skills; clear communication.'],
            ['title' => 'Computer Service Technician', 'pay' => '₱19,000–₱24,000 monthly', 'description' => 'Set up workstations, perform routine maintenance, document service requests, and help staff resolve common hardware and network issues.', 'requirements' => 'Computer Systems Servicing training or equivalent experience; basic networking; customer service; willingness to travel between sites.'],
            ['title' => 'Kitchen Production Assistant', 'pay' => '₱16,500–₱20,000 monthly', 'description' => 'Prepare ingredients, follow kitchen production plans, maintain sanitation logs, check stock rotation, and support safe food handling.', 'requirements' => 'Food preparation experience or relevant training; food safety awareness; dependable attendance; ability to work shifts.'],
            ['title' => 'Bakery Production Staff', 'pay' => '₱17,000–₱21,000 monthly', 'description' => 'Measure ingredients, assist with dough preparation and baking schedules, record daily output, and keep the production area sanitary.', 'requirements' => 'Bread and pastry training or equivalent experience; accurate measuring; safe equipment use; teamwork.'],
            ['title' => 'Electrical Maintenance Aide', 'pay' => '₱19,000–₱25,000 monthly', 'description' => 'Assist qualified staff with scheduled inspections, maintenance logs, parts preparation, and safe work-area setup.', 'requirements' => 'Relevant electrical training; knowledge of lockout and safety procedures; ability to follow technical instructions.'],
            ['title' => 'Welding Shop Assistant', 'pay' => '₱18,000–₱24,000 monthly', 'description' => 'Prepare materials, maintain welding tools, follow workshop safety rules, and support fabrication and quality-check activities.', 'requirements' => 'Basic welding skills or training; PPE and workshop safety awareness; ability to read simple work instructions.'],
            ['title' => 'Farm Operations Assistant', 'pay' => '₱16,000–₱20,000 monthly', 'description' => 'Help plan crop activities, maintain field records, prepare growing areas, monitor harvest handling, and coordinate daily farm tasks.', 'requirements' => 'Agriculture training or related experience; practical record keeping; outdoor work readiness; teamwork.'],
            ['title' => 'Vehicle Service Assistant', 'pay' => '₱18,000–₱23,000 monthly', 'description' => 'Prepare vehicles for inspection, support routine servicing, keep service bays organized, and record work completed for each vehicle.', 'requirements' => 'Automotive servicing training or related experience; safe tool handling; attention to detail; customer courtesy.'],
            ['title' => 'Delivery Driver', 'pay' => '₱17,000–₱22,000 monthly', 'description' => 'Complete scheduled local deliveries, inspect the vehicle before trips, confirm delivery documents, and report route or vehicle issues.', 'requirements' => 'Appropriate valid driver’s licence; safe driving record; familiarity with Pangasinan routes; professional customer service.'],
            ['title' => 'Housekeeping Attendant', 'pay' => '₱16,000–₱19,000 monthly', 'description' => 'Prepare assigned rooms and common areas, replenish supplies, respond courteously to guest requests, and report maintenance concerns.', 'requirements' => 'Housekeeping training or related experience; safe chemical handling; dependable attendance; respectful communication.'],
        ];

        foreach ($employers as $employerIndex => $employerData) {
            $employer = Employer::query()->firstOrCreate(
                ['name' => $employerData['name']],
                ['location' => $employerData['location'], 'is_verified' => false, 'is_demo' => true],
            );

            foreach ($roles as $role) {
                $job = JobOpportunity::query()->firstOrNew([
                    'employer_id' => $employer->id,
                    'title' => $role['title'],
                    'is_demo' => true,
                ]);
                $job->fill([
                    'employer_name' => $employer->name,
                    'employer_verified' => false,
                    'location' => $employer->location,
                    'compensation' => $role['pay'],
                    'description' => 'SIMULATED DEMO RECORD — '.$role['description'].' This fictional vacancy is paired with '.$employer->name.' only to test Tuklas search, recommendations, and application handoffs.',
                    'requirements' => $role['requirements'].' Fictional test criteria; no actual employer is recruiting for this record.',
                    'application_url' => null,
                    'source_url' => null,
                    'source_checked_at' => null,
                    'closes_at' => null,
                    'status' => 'published',
                    'posted_by' => $adminId,
                ])->save();
            }
        }

        $providers = [
            'Baybay Norte Learning Foundation',
            'Amianan Youth Development Fund',
            'Pangasinan Coast Community Trust',
            'Balon Skills Advancement Fund',
            'Umaani Agricultural Futures Foundation',
        ];
        $scholarships = [
            ['title' => 'Technical Skills Starter Grant', 'eligibility' => 'For a fictional Pangasinan resident beginning an eligible short-course training pathway.', 'requirements' => 'Demo checklist: learner profile, proof of residence, and a short statement of training goals.', 'assessment_type' => 'Interview', 'assessment_details' => 'Simulated 15-minute interview about training goals and availability.'],
            ['title' => 'Women in Skilled Trades Award', 'eligibility' => 'For a fictional applicant entering a construction, electrical, or fabrication training pathway.', 'requirements' => 'Demo checklist: learner profile, selected qualification, and a brief skills-interest statement.', 'assessment_type' => 'Skills assessment', 'assessment_details' => 'Simulated basic safety and tool-recognition activity; no real assessment is scheduled.'],
            ['title' => 'Digital Work Readiness Bursary', 'eligibility' => 'For a fictional career seeker pursuing bookkeeping, computer servicing, or digital office skills.', 'requirements' => 'Demo checklist: learner profile, education history, and a sample work-readiness plan.', 'assessment_type' => 'Exam', 'assessment_details' => 'Simulated reading, numeracy, and computer-literacy assessment.'],
            ['title' => 'Community Food Enterprise Scholarship', 'eligibility' => 'For a fictional applicant interested in cookery, baking, or local food enterprise.', 'requirements' => 'Demo checklist: learner profile, proposed training course, and a short community-enterprise plan.', 'assessment_type' => 'Other', 'assessment_details' => 'Simulated review of a sample product and simple costing worksheet.'],
            ['title' => 'Farm Innovation Training Award', 'eligibility' => 'For a fictional Pangasinan learner pursuing agriculture or food-production training.', 'requirements' => 'Demo checklist: learner profile, agriculture interest, and a sample seasonal learning plan.', 'assessment_type' => 'Interview', 'assessment_details' => 'Simulated conversation about farm interests and availability.'],
            ['title' => 'Automotive Service Pathway Grant', 'eligibility' => 'For a fictional applicant beginning automotive service or vehicle maintenance training.', 'requirements' => 'Demo checklist: learner profile, selected course, and a sample workshop-safety commitment.', 'assessment_type' => 'Skills assessment', 'assessment_details' => 'Simulated identification of workshop tools and safety steps.'],
            ['title' => 'Hospitality Career Launch Award', 'eligibility' => 'For a fictional applicant pursuing housekeeping or hospitality services training.', 'requirements' => 'Demo checklist: learner profile, schedule availability, and a sample customer-service response.', 'assessment_type' => 'Interview', 'assessment_details' => 'Simulated customer-service role-play.'],
            ['title' => 'Local Transport Skills Bursary', 'eligibility' => 'For a fictional learner pursuing professional driving and transport-related skills.', 'requirements' => 'Demo checklist: learner profile, valid licence details for the exercise, and a road-safety statement.', 'assessment_type' => 'Exam', 'assessment_details' => 'Simulated road-safety knowledge quiz; this is not a licensing assessment.'],
            ['title' => 'Returning Learner Skills Grant', 'eligibility' => 'For a fictional adult learner returning to training after a career break.', 'requirements' => 'Demo checklist: learner profile, selected qualification, and a training availability plan.', 'assessment_type' => 'None', 'assessment_details' => 'No assessment in this fictional scenario.'],
            ['title' => 'First Qualification Access Award', 'eligibility' => 'For a fictional learner seeking a first formal technical qualification.', 'requirements' => 'Demo checklist: learner profile, education information, and course preference.', 'assessment_type' => 'Other', 'assessment_details' => 'Simulated document review for workflow demonstration.'],
        ];

        foreach ($providers as $provider) {
            foreach ($scholarships as $scholarshipIndex => $scholarshipData) {
                Scholarship::query()->firstOrCreate(
                    ['title' => $scholarshipData['title'], 'provider' => $provider],
                    [
                        'description' => 'SIMULATED DEMO RECORD — '.$scholarshipData['title'].' is a fictional funding scenario created to exercise Tuklas eligibility, requirements, assessment, and deadline screens. It is not a real scholarship or provider offer.',
                        'eligibility' => $scholarshipData['eligibility'],
                        'requirements' => $scholarshipData['requirements'],
                        'assessment_type' => $scholarshipData['assessment_type'],
                        'assessment_details' => $scholarshipData['assessment_details'],
                        'source_url' => null,
                        'application_url' => null,
                        'deadline' => today()->addMonths(3 + ($scholarshipIndex % 6)),
                        'status' => 'published',
                        'verified_at' => now(),
                        'published_by' => $adminId,
                        'is_demo' => true,
                    ],
                );
            }
        }

        $this->seedTrainingActivity($adminId);

        $this->command?->info('Seeded 50 fictional training listings, 50 fictional job listings, 50 fictional scholarship scenarios, 50 fictional training dates, 50 fictional training requests, and 50 fictional assessment records. Descriptions identify fictional listings as test data.');
    }

    private function seedTrainingActivity(int $adminId): void
    {
        $demoApplicants = [
            ['name' => 'Maria Teresa Dela Cruz', 'email' => 'maria.delacruz@demo.tuklas.test'],
            ['name' => 'Daniel Santos', 'email' => 'daniel.santos@demo.tuklas.test'],
            ['name' => 'Angela Mae Ramos', 'email' => 'angela.ramos@demo.tuklas.test'],
            ['name' => 'Rafael Mendoza', 'email' => 'rafael.mendoza@demo.tuklas.test'],
            ['name' => 'Lourdes Castillo', 'email' => 'lourdes.castillo@demo.tuklas.test'],
        ];
        $applications = [];
        $sessionIndex = 0;

        foreach (TrainingCenter::query()->where('is_demo', true)->orderBy('id')->get() as $center) {
            $programs = TrainingProgram::query()->where('training_center_id', $center->id)->where('is_demo', true)->orderBy('id')->get();
            foreach ($programs as $program) {
                $startsAt = today()->addDays($sessionIndex - 15)->setTime(9, 0, 0);
                $session = TrainingSession::query()->firstOrNew([
                    'training_program_id' => $program->id,
                    'is_demo' => true,
                ]);
                $session->fill([
                    'training_center_id' => $center->id,
                    'ends_at' => $startsAt->copy()->addHours(6),
                    'capacity' => 20,
                    'starts_at' => $startsAt,
                    'status' => $sessionIndex > 15 ? 'open' : 'closed',
                    'created_by' => $adminId,
                    'is_demo' => true,
                ])->save();

                $applicantData = $demoApplicants[$sessionIndex % count($demoApplicants)];
                $applicant = User::query()->firstOrCreate(
                    ['email' => $applicantData['email']],
                    [
                        'name' => $applicantData['name'],
                        'password' => Hash::make(Str::random(40)),
                        'email_verified_at' => now(),
                        'role' => Role::Youth,
                        'is_active' => true,
                    ],
                );
                $application = TrainingApplication::query()->firstOrNew([
                    'training_program_id' => $program->id,
                    'user_id' => $applicant->id,
                ]);
                $application->fill([
                    'training_session_id' => $session->id,
                    'status' => 'accepted',
                    'is_demo' => true,
                ])->save();
                $applications[$sessionIndex] = [
                    'application' => $application,
                    'starts_at' => $startsAt,
                    'is_completed' => $sessionIndex <= 10,
                ];

                $sessionIndex++;
            }
        }

        foreach ($applications as $applicantIndex => $activity) {
            $application = $activity['application'];
            $isCompleted = $activity['is_completed'];
            $result = $isCompleted ? ($applicantIndex % 4 === 0 ? 'failed' : 'passed') : 'pending';
            $assessment = AssessmentRecord::query()->firstOrNew(['training_application_id' => $application->id]);
            $assessment->fill([
                'scheduled_at' => $isCompleted
                    ? $activity['starts_at']->copy()->addDays(5)
                    : $activity['starts_at']->copy()->addDays(10),
                'status' => $isCompleted ? 'completed' : 'scheduled',
                'result' => $result,
                'notes' => 'SIMULATED DEMO RECORD — '.$result.' result for workflow testing only; this is not an official TESDA assessment result.',
                'recorded_by' => $adminId,
                'is_demo' => true,
            ])->save();
        }
    }
}
