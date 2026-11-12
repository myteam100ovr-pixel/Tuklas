<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AssessmentRecord;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\ProfileCompletion;
use Database\Seeders\FictionalDemoCatalogSeeder;
use Database\Seeders\LocalWorkflowDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FictionalDemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_fifty_deterministic_records_for_catalogs_and_training_activity(): void
    {
        User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);
        $this->seed(FictionalDemoCatalogSeeder::class);

        $this->assertDatabaseCount('training_centers', 5);
        $this->assertDatabaseCount('training_programs', 50);
        $this->assertDatabaseCount('employers', 5);
        $this->assertDatabaseCount('job_opportunities', 50);
        $this->assertDatabaseCount('scholarships', 50);
        $this->assertSame(50, TrainingProgram::query()->where('is_demo', true)->count());
        $this->assertSame(50, JobOpportunity::query()->where('is_demo', true)->where('employer_verified', false)->count());
        $this->assertSame(50, Scholarship::query()->where('is_demo', true)->count());
        $this->assertSame(5, TrainingCenter::query()->where('is_demo', true)->count());
        $this->assertSame(50, TrainingSession::query()->where('is_demo', true)->count());
        $this->assertSame(50, TrainingApplication::query()->where('is_demo', true)->count());
        $this->assertSame(50, AssessmentRecord::query()->where('is_demo', true)->count());
    }

    public function test_career_seekers_see_fictional_records_without_demo_badges(): void
    {
        User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $jobs = $this->actingAs($careerSeeker)->get(route('opportunities.index'))
            ->assertSee('Accounts Assistant')
            ->assertDontSee('Simulated demo data')
            ->assertSee('no actual employer is recruiting');
        $this->assertCount(50, $jobs->viewData('jobs'));
        $scholarships = $this->get(route('scholarships.index'))
            ->assertSee('Technical Skills Starter Grant')
            ->assertDontSee('Simulated demo data')
            ->assertSee('not a real scholarship');
        $this->assertCount(50, $scholarships->viewData('scholarships'));
        $this->get(route('centers.index'))
            ->assertSee('Baybay Norte Skills Institute')
            ->assertDontSee('Simulated demo data');
        $this->get(route('tesda.index'))
            ->assertSee('Bookkeeping NC III')
            ->assertDontSee('Simulated demo data');

        $this->getJson('/api/mobile/opportunities')->assertOk()
            ->assertJsonFragment(['is_demo' => true]);
        $this->getJson('/api/mobile/scholarships')->assertOk()
            ->assertJsonFragment(['is_demo' => true])
            ->assertJsonStructure(['myApplications', 'applications']);
        $this->getJson('/api/mobile/training-centers')->assertOk()
            ->assertJsonFragment(['is_demo' => true]);
        $this->getJson('/api/mobile/tesda')->assertOk()
            ->assertJsonFragment(['is_demo' => true]);
    }

    public function test_all_mobile_roles_can_browse_seeded_public_catalogs_and_upcoming_sessions(): void
    {
        User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);
        $centerId = TrainingCenter::query()->where('is_demo', true)->orderByDesc('id')->value('id');

        foreach ([Role::Admin, Role::TesdaAdmin, Role::Trainer, Role::CenterRepresentative, Role::Youth] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
                'training_center_id' => $role === Role::CenterRepresentative ? $centerId : null,
            ]);

            $this->actingAs($user)->getJson('/api/mobile/opportunities')->assertOk()
                ->assertJsonFragment(['title' => 'Accounts Assistant']);
            $this->getJson('/api/mobile/scholarships')->assertOk()
                ->assertJsonFragment(['title' => 'Technical Skills Starter Grant']);
            $this->getJson('/api/mobile/training-centers')->assertOk()
                ->assertJsonFragment(['title' => 'Bookkeeping NC III']);
            $this->getJson('/api/mobile/tesda')->assertOk()
                ->assertJsonFragment(['title' => 'Bookkeeping NC III']);
            $this->getJson('/api/mobile/training-sessions')->assertOk()
                ->assertJsonStructure(['sessions' => ['data']])
                ->assertJsonFragment(['title' => 'Bookkeeping NC III']);
        }
    }

    public function test_staff_can_review_simulated_training_dates_requests_and_results(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);

        $sessions = $this->actingAs($admin)->get(route('sessions.index'))
            ->assertSee('Bookkeeping NC III')
            ->assertDontSee('Simulated demo data');
        $this->assertCount(50, $sessions->viewData('sessions'));
        $sessions->assertDontSee('Simulated demo data');

        $applications = $this->get(route('applications.index'))
            ->assertSee('Maria Teresa Dela Cruz')
            ->assertDontSee('Simulated demo data');
        $this->assertCount(50, $applications->viewData('applications'));

        $assessments = $this->get(route('assessments.index'))
            ->assertSee('Angela Mae Ramos')
            ->assertDontSee('Simulated demo data');
        $this->assertCount(50, $assessments->viewData('records'));
    }

    public function test_local_workflow_seeder_populates_the_career_seeker_demo_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);
        $center = TrainingCenter::query()->where('is_demo', true)->oldest('id')->firstOrFail();
        $careerSeeker = User::factory()->create([
            'email' => config('tuklas.demo_accounts.career_seeker_email'),
            'role' => Role::Youth,
            'is_active' => true,
        ]);
        $representative = User::factory()->create([
            'role' => Role::CenterRepresentative,
            'training_center_id' => $center->id,
            'is_active' => true,
        ]);

        $this->seed(LocalWorkflowDataSeeder::class);
        $this->seed(LocalWorkflowDataSeeder::class);

        $careerSeeker->refresh()->load('youthProfile');
        $this->assertSame(100, ProfileCompletion::for($careerSeeker)['percent']);
        $this->assertDatabaseCount('training_applications', 53);
        $this->assertSame(3, $careerSeeker->trainingApplications()->count());
        $this->assertSame(2, AssessmentRecord::query()->whereHas('application', fn ($query) => $query->where('user_id', $careerSeeker->id))->count());

        $this->actingAs($careerSeeker)->get(route('applications.index'))
            ->assertOk()
            ->assertSee('Training applications')
            ->assertSee('Pending')
            ->assertSee('Accepted');
        $this->actingAs($careerSeeker)->get(route('assessments.index'))
            ->assertOk()
            ->assertSee('Assessment records')
            ->assertSee('passed');
        $this->actingAs($representative)->get(route('applications.index'))
            ->assertOk()
            ->assertSee($careerSeeker->name)
            ->assertSee('Pending');
        $this->actingAs($admin)->get(route('assessments.index'))
            ->assertOk()
            ->assertSee($careerSeeker->name);
    }
}
