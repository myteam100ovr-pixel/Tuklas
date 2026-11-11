<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\LocalSampleCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LocalSampleCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_repeatable_fictional_catalog_records_with_sample_labels(): void
    {
        User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);

        (new LocalSampleCatalogSeeder)->run();
        (new LocalSampleCatalogSeeder)->run();

        $this->assertSame(2, TrainingCenter::query()->where('name', 'like', '[SAMPLE]%')->count());
        $this->assertSame(50, TrainingProgram::query()->where('title', 'like', '[SAMPLE]%')->count());
        $this->assertSame(50, Employer::query()->where('name', 'like', '[SAMPLE]%')->count());
        $this->assertSame(50, JobOpportunity::query()->where('title', 'like', '[SAMPLE]%')->count());
        $this->assertSame(50, Scholarship::query()->where('title', 'like', '[SAMPLE]%')->count());

        $this->assertDatabaseHas('training_programs', [
            'title' => '[SAMPLE] Qualification 01',
            'status' => 'published',
            'source_reference' => 'SAMPLE DATA — fictional record generated for testing; not a real qualification or provider.',
        ]);

        $this->assertDatabaseHas('job_opportunities', [
            'title' => '[SAMPLE] Accounts Assistant 01',
            'status' => 'published',
            'source_url' => null,
        ]);

        $this->assertDatabaseHas('scholarships', [
            'title' => '[SAMPLE] Training Support Grant 01',
            'status' => 'published',
            'source_url' => null,
            'assessment_type' => 'Sample assessment',
        ]);

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($youth)->get(route('opportunities.index'))
            ->assertOk()
            ->assertSee('[SAMPLE] Accounts Assistant 01');

        $this->actingAs($youth)->get(route('scholarships.index'))
            ->assertOk()
            ->assertSee('[SAMPLE] Training Support Grant 01');

        $this->actingAs($youth)->get(route('tesda.index'))
            ->assertOk()
            ->assertSee('[SAMPLE] Qualification 01');

        $tesdaAdmin = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);

        $this->actingAs($tesdaAdmin)->get(route('tesda.index'))
            ->assertOk()
            ->assertSee('[SAMPLE] Qualification 01');
    }

    public function test_production_sample_seeding_requires_the_explicit_configuration_flag(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['tuklas.sample_catalog.allow_in_production' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Enable TUKLAS_ALLOW_SAMPLE_CATALOG_IN_PRODUCTION');

        (new LocalSampleCatalogSeeder)->run();
    }

    public function test_production_sample_seeding_runs_after_the_explicit_configuration_flag_is_enabled(): void
    {
        User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['tuklas.sample_catalog.allow_in_production' => true]);

        (new LocalSampleCatalogSeeder)->run();

        $this->assertSame(50, TrainingProgram::query()->where('title', 'like', '[SAMPLE]%')->count());
        $this->assertSame(50, JobOpportunity::query()->where('title', 'like', '[SAMPLE]%')->count());
        $this->assertSame(50, Scholarship::query()->where('title', 'like', '[SAMPLE]%')->count());
    }
}
