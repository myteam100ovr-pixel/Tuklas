<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\User;
use Database\Seeders\PhilJobNetPangasinanSeeder;
use Database\Seeders\TesdaScholarshipInfoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealPublicCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_repeatable_pangasinan_job_sources_as_staff_review_drafts(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->seed(PhilJobNetPangasinanSeeder::class);
        $this->seed(PhilJobNetPangasinanSeeder::class);

        $this->assertDatabaseCount('job_opportunities', 3);
        $this->assertDatabaseHas('job_opportunities', [
            'title' => 'MECHANICAL ENGINEERING TECHNICIAN (GENERAL)',
            'location' => 'Calasiao, Pangasinan',
            'compensation' => '₱25,000.00',
            'source_url' => 'https://philjobnet.gov.ph/job-vacancies/job/mechanical-engineering-technician-general-1503469',
            'status' => 'draft',
            'employer_verified' => false,
        ]);

        $this->actingAs($admin)->get(route('opportunities.index'))
            ->assertOk()
            ->assertSee('ACCOUNTS OFFICER')
            ->assertSee('MECHANICAL ENGINEERING TECHNICIAN (GENERAL)')
            ->assertSee('Source listing');

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->get(route('opportunities.index'))
            ->assertOk()
            ->assertDontSee('ACCOUNTS OFFICER');

        $this->assertSame(3, JobOpportunity::count());
    }

    public function test_it_adds_source_linked_tesda_scholarship_information_without_claiming_local_slots(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->seed(TesdaScholarshipInfoSeeder::class);
        $this->seed(TesdaScholarshipInfoSeeder::class);

        $this->assertDatabaseCount('scholarships', 1);
        $this->assertDatabaseHas('scholarships', [
            'title' => 'Private Education Student Financial Assistance (PESFA)',
            'source_url' => 'https://www.tesda.gov.ph/About/TESDA/89',
            'requirements' => 'Not specified by provider for a current Pangasinan offering. Contact the TESDA Provincial or District Office to confirm requirements and available slots.',
            'assessment_type' => 'Not specified by provider',
            'status' => 'draft',
            'verified_at' => null,
        ]);

        $this->actingAs($admin)->get(route('scholarships.index'))
            ->assertOk()
            ->assertSee('Private Education Student Financial Assistance (PESFA)')
            ->assertSee('Check provider source');

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->get(route('scholarships.index'))
            ->assertOk()
            ->assertDontSee('Private Education Student Financial Assistance (PESFA)');

        $this->assertSame(1, Scholarship::count());
    }
}
