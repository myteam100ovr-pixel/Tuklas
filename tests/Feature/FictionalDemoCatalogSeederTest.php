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
use Database\Seeders\FictionalDemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FictionalDemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_fifty_deterministic_demo_records_for_each_public_catalog(): void
    {
        User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
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
        $this->assertSame(10, TrainingSession::query()->where('is_demo', true)->count());
        $this->assertSame(5, TrainingApplication::query()->where('is_demo', true)->count());
        $this->assertSame(3, AssessmentRecord::query()->where('is_demo', true)->count());
    }

    public function test_career_seekers_see_simulated_records_labeled_on_web_and_mobile(): void
    {
        User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($careerSeeker)->get(route('opportunities.index'))
            ->assertSee('Accounts Assistant')
            ->assertSee('Simulated demo data')
            ->assertSee('no actual employer is recruiting');
        $this->get(route('scholarships.index'))
            ->assertSee('Technical Skills Starter Grant')
            ->assertSee('Simulated demo data')
            ->assertSee('not a real scholarship');
        $this->get(route('centers.index'))
            ->assertSee('Baybay Norte Skills Institute')
            ->assertSee('Simulated demo data');
        $this->get(route('tesda.index'))
            ->assertSee('Bookkeeping NC III')
            ->assertSee('Simulated demo data');

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

    public function test_staff_can_review_simulated_training_dates_requests_and_results(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->seed(FictionalDemoCatalogSeeder::class);

        $this->actingAs($admin)->get(route('sessions.index'))
            ->assertSee('Simulated demo data')
            ->assertSee('Bookkeeping NC III');
        $this->get(route('applications.index'))
            ->assertSee('Maria Teresa Dela Cruz')
            ->assertSee('Simulated demo data');
        $this->get(route('assessments.index'))
            ->assertSee('Angela Mae Ramos')
            ->assertSee('Simulated demo data');
    }
}
