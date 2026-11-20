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
            ->where('role', Role::Admin->value)
            ->oldest('id')
            ->value('id');

        if ($adminId === null) {
            throw new RuntimeException('Create the PESO administrator before seeding catalog records.');
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
            ['Driving NC II', 'Practice defensive driving, vehicle checks, traffic awareness, route planning, and professional passenger communication.', 15],
            ['Housekeeping NC II', 'Practice room preparation, cleaning procedures, linen handling, guest requests, safe chemical use, and quality checks.', 55],
        ];

        foreach ($centers as $centerIndex => $centerData) {
            $center = TrainingCenter::query()->firstOrCreate(
                ['name' => $centerData['name']],
                ['type' => 'PTC', 'address' => $centerData['address'], 'is_active' => true],
            );

            foreach ($courses as $courseIndex => [$title, $description, $durationDays]) {
                $sourceReference = 'tuklas://training/'.$centerIndex.'-'.$courseIndex;
                $program = TrainingProgram::query()->firstOrNew([
                    'training_center_id' => $center->id,
                    'source_reference' => $sourceReference,
                ]);
                $program->fill([
                    'title' => $title,
                    'nc_level' => preg_match('/NC (I{1,3}|IV)\b/', $title, $level) ? 'NC '.$level[1] : null,
                    'duration_days' => $durationDays,
                    'description' => $description,
                    'requirements' => 'Applicants should prepare a learner profile and valid identification. Related experience may be helpful for some qualifications.',
                    'schedule_note' => 'Contact the training center to confirm current dates, fees, entry requirements, and available places.',
                    'status' => 'published',
                    'last_verified_at' => null,
                    'created_by' => $adminId,
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
                ['location' => $employerData['location'], 'is_verified' => true],
            );
            $employer->update(['is_verified' => true]);

            foreach ($roles as $role) {
                $job = JobOpportunity::query()->firstOrNew([
                    'employer_id' => $employer->id,
                    'title' => $role['title'],
                ]);
                $job->fill([
                    'employer_name' => $employer->name,
                    'employer_verified' => true,
                    'location' => $employer->location,
                    'compensation' => $role['pay'],
                    'description' => $role['description'],
                    'requirements' => $role['requirements'],
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
            ['title' => 'Technical Skills Starter Grant', 'description' => 'Financial support for learners starting a short technical course, including help with training expenses and essential materials.', 'eligibility' => 'Applicants living in Pangasinan who are beginning an eligible short-course training program.', 'requirements' => 'Submit a learner profile, proof of residence, and a short statement describing your training goals.', 'assessment_type' => 'Interview', 'assessment_details' => 'A short interview about your training goals and availability.'],
            ['title' => 'Women in Skilled Trades Award', 'description' => 'Support for women preparing for careers in construction, electrical work, fabrication, and related trades.', 'eligibility' => 'Applicants entering a construction, electrical, or fabrication training program.', 'requirements' => 'Submit a learner profile, selected qualification, and a brief statement about your interest in the trade.', 'assessment_type' => 'Skills assessment', 'assessment_details' => 'A basic safety and tool-recognition activity.'],
            ['title' => 'Digital Work Readiness Bursary', 'description' => 'Assistance for learners building practical bookkeeping, computer servicing, and digital office skills.', 'eligibility' => 'Applicants pursuing bookkeeping, computer servicing, or digital office skills.', 'requirements' => 'Submit a learner profile, education history, and a brief work-readiness plan.', 'assessment_type' => 'Exam', 'assessment_details' => 'A short assessment covering reading, numeracy, and computer literacy.'],
            ['title' => 'Community Food Enterprise Scholarship', 'description' => 'Training assistance for people interested in cookery, baking, and small food businesses in their communities.', 'eligibility' => 'Applicants interested in cookery, baking, or local food enterprise.', 'requirements' => 'Submit a learner profile, preferred training course, and a short community enterprise plan.', 'assessment_type' => 'Other', 'assessment_details' => 'A review of a sample product and a simple costing worksheet.'],
            ['title' => 'Farm Innovation Training Award', 'description' => 'Support for learners developing skills in agriculture and food production.', 'eligibility' => 'Pangasinan applicants pursuing agriculture or food-production training.', 'requirements' => 'Submit a learner profile, describe your agriculture interests, and provide a seasonal learning plan.', 'assessment_type' => 'Interview', 'assessment_details' => 'An interview about your farm interests and training availability.'],
            ['title' => 'Automotive Service Pathway Grant', 'description' => 'Assistance for learners beginning automotive service or vehicle maintenance training.', 'eligibility' => 'Applicants beginning automotive service or vehicle maintenance training.', 'requirements' => 'Submit a learner profile, selected course, and a commitment to workshop safety.', 'assessment_type' => 'Skills assessment', 'assessment_details' => 'Identify common workshop tools and explain basic safety steps.'],
            ['title' => 'Hospitality Career Launch Award', 'description' => 'Training support for people preparing for housekeeping and hospitality service roles.', 'eligibility' => 'Applicants pursuing housekeeping or hospitality services training.', 'requirements' => 'Submit a learner profile, schedule availability, and a short customer-service response.', 'assessment_type' => 'Interview', 'assessment_details' => 'A brief customer-service role-play.'],
            ['title' => 'Local Transport Skills Bursary', 'description' => 'Support for learners pursuing professional driving and transport-related skills.', 'eligibility' => 'Applicants pursuing professional driving and transport-related training.', 'requirements' => 'Submit a learner profile, relevant licence details, and a road-safety statement.', 'assessment_type' => 'Exam', 'assessment_details' => 'A road-safety knowledge assessment.'],
            ['title' => 'Returning Learner Skills Grant', 'description' => 'A training grant for adults returning to education after a career break.', 'eligibility' => 'Adult learners returning to training after a career break.', 'requirements' => 'Submit a learner profile, selected qualification, and a training availability plan.', 'assessment_type' => 'None', 'assessment_details' => 'No assessment is required.'],
            ['title' => 'First Qualification Access Award', 'description' => 'Support for learners working toward their first formal technical qualification.', 'eligibility' => 'Applicants seeking a first formal technical qualification.', 'requirements' => 'Submit a learner profile, education information, and preferred course.', 'assessment_type' => 'Other', 'assessment_details' => 'Required documents will be reviewed for completeness.'],
        ];

        foreach ($providers as $providerIndex => $provider) {
            $center = TrainingCenter::query()
                ->where('name', $centers[$providerIndex]['name'])
                ->firstOrFail();
            $programIds = TrainingProgram::query()
                ->where('training_center_id', $center->id)
                ->orderBy('title')
                ->pluck('id')
                ->values();

            foreach ($scholarships as $scholarshipIndex => $scholarshipData) {
                $scholarship = Scholarship::query()->firstOrNew([
                    'title' => $scholarshipData['title'],
                    'provider' => $provider,
                ]);
                $scholarship->fill([
                    'description' => $scholarshipData['description'],
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
                    'training_center_id' => $center->id,
                    'training_program_id' => $programIds->get($scholarshipIndex % $programIds->count()),
                    'slot_limit' => 10 + ($scholarshipIndex % 6),
                ])->save();
            }
        }

        $this->seedTrainingActivity($adminId, array_column($centers, 'name'));

        $this->command?->info('Training courses, job opportunities, scholarships, schedules, applications, and assessments are ready.');
    }

    private function seedTrainingActivity(int $adminId, array $centerNames): void
    {
        $careerSeekers = [
            ['name' => 'Maria Teresa Dela Cruz', 'email' => 'maria.delacruz@example.org'],
            ['name' => 'Daniel Santos', 'email' => 'daniel.santos@example.org'],
            ['name' => 'Angela Mae Ramos', 'email' => 'angela.ramos@example.org'],
            ['name' => 'Rafael Mendoza', 'email' => 'rafael.mendoza@example.org'],
            ['name' => 'Lourdes Castillo', 'email' => 'lourdes.castillo@example.org'],
        ];
        $applications = [];
        $sessionIndex = 0;

        foreach (TrainingCenter::query()->whereIn('name', $centerNames)->orderBy('id')->get() as $center) {
            $programs = TrainingProgram::query()->where('training_center_id', $center->id)->orderBy('id')->get();
            foreach ($programs as $program) {
                $startsAt = today()->addDays($sessionIndex - 15)->setTime(9, 0, 0);
                $session = TrainingSession::query()->firstOrNew([
                    'training_program_id' => $program->id,
                ]);
                $session->fill([
                    'training_center_id' => $center->id,
                    'ends_at' => $startsAt->copy()->addHours(6),
                    'capacity' => 20,
                    'starts_at' => $startsAt,
                    'status' => $sessionIndex > 15 ? 'open' : 'closed',
                    'created_by' => $adminId,
                ])->save();

                $applicantData = $careerSeekers[$sessionIndex % count($careerSeekers)];
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
                'notes' => 'Assessment result recorded as '.$result.'.',
                'recorded_by' => $adminId,
            ])->save();
        }
    }
}
