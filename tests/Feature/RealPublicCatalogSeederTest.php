<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employer;
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

    public function test_it_publishes_real_source_checked_pangasinan_jobs_and_closes_the_unverified_search_result(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $employer = Employer::factory()->create(['name' => 'Asiawide Human Resources and Consultancy, Corp']);
        JobOpportunity::factory()->create([
            'employer_id' => $employer->id,
            'employer_name' => $employer->name,
            'title' => 'PRODUCT SPECIALIST',
            'source_url' => 'https://philjobnet.gov.ph/job-vacancies/0/product%20specialist/0',
            'status' => 'draft',
        ]);

        $this->seed(PhilJobNetPangasinanSeeder::class);
        $this->seed(PhilJobNetPangasinanSeeder::class);

        $this->assertDatabaseCount('job_opportunities', 3);
        $this->assertDatabaseHas('job_opportunities', [
            'title' => 'MECHANICAL ENGINEERING TECHNICIAN (GENERAL)',
            'location' => 'Calasiao, Pangasinan',
            'compensation' => '₱25,000.00',
            'source_url' => 'https://philjobnet.gov.ph/job-vacancies/job/mechanical-engineering-technician-general-1503469',
            'status' => 'published',
            'employer_verified' => true,
            'source_checked_at' => '2026-11-11 00:00:00',
        ]);
        $this->assertDatabaseHas('job_opportunities', [
            'title' => 'PRODUCT SPECIALIST',
            'status' => 'closed',
        ]);

        $this->actingAs($admin)->get(route('opportunities.index'))
            ->assertOk()
            ->assertSee('ACCOUNTS OFFICER')
            ->assertSee('MECHANICAL ENGINEERING TECHNICIAN (GENERAL)')
            ->assertSee('Prepare collection itineraries')
            ->assertSee('A graduate of BSBA or another business-related course')
            ->assertSee('WATERMATIC PHILIPPINES CORPORATION')
            ->assertSee('Source listing')
            ->assertSee('PRODUCT SPECIALIST')
            ->assertSee('Closed');

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->get(route('opportunities.index'))
            ->assertOk()
            ->assertSee('ACCOUNTS OFFICER')
            ->assertSee('MECHANICAL ENGINEERING TECHNICIAN (GENERAL)')
            ->assertDontSee('PRODUCT SPECIALIST');

        $this->assertSame(3, JobOpportunity::count());

        $this->actingAs($youth)->getJson('/api/mobile/opportunities')
            ->assertOk()
            ->assertJsonFragment(['title' => 'ACCOUNTS OFFICER'])
            ->assertJsonFragment(['title' => 'MECHANICAL ENGINEERING TECHNICIAN (GENERAL)'])
            ->assertJsonMissing(['title' => 'PRODUCT SPECIALIST']);
    }

    public function test_it_publishes_official_tesda_scholarship_information_without_claiming_local_slots(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->seed(TesdaScholarshipInfoSeeder::class);
        $this->seed(TesdaScholarshipInfoSeeder::class);

        $this->assertDatabaseCount('scholarships', 1);
        $this->assertDatabaseHas('scholarships', [
            'title' => 'Private Education Student Financial Assistance (PESFA)',
            'source_url' => 'https://www.tesda.gov.ph/About/TESDA/1279',
            'requirements' => 'Not specified by provider for a current Pangasinan offering. Contact the TESDA Provincial or District Office to confirm requirements and available slots.',
            'assessment_type' => 'Not specified by provider',
            'status' => 'published',
            'verified_at' => '2026-11-11 00:00:00',
        ]);

        $this->actingAs($admin)->get(route('scholarships.index'))
            ->assertOk()
            ->assertSee('Private Education Student Financial Assistance (PESFA)')
            ->assertSee('Check provider source');

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->get(route('scholarships.index'))
            ->assertOk()
            ->assertSee('Private Education Student Financial Assistance (PESFA)')
            ->assertSee('not a confirmed open Pangasinan scholarship')
            ->assertSee('Check provider source');

        $this->getJson('/api/mobile/scholarships')->assertOk()
            ->assertJsonFragment(['title' => 'Private Education Student Financial Assistance (PESFA)'])
            ->assertJsonFragment(['assessment_type' => 'Not specified by provider']);

        $this->assertSame(1, Scholarship::count());
    }
}
