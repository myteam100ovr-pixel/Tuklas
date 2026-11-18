<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\PangasinanTrainingCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PangasinanTrainingCenterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_repeatable_pangasinan_center_offerings_and_shows_them_to_staff(): void
    {
        $this->seed(PangasinanTrainingCenterSeeder::class);
        $this->seed(PangasinanTrainingCenterSeeder::class);

        $this->assertDatabaseCount('training_centers', 2);
        $this->assertDatabaseCount('training_programs', 44);

        $provincialCenter = TrainingCenter::query()->where('type', 'PTC')->sole();
        $this->assertSame('Provincial Capitol Compound, Lingayen, Pangasinan', $provincialCenter->address);
        $this->assertSame('075-542-4989', $provincialCenter->contact_phone);
        $organicAgriculture = $provincialCenter->programs()->where('title', 'Organic Agriculture Production NC II')->sole();
        $this->assertSame(29, $organicAgriculture->duration_days);
        $this->assertSame('published', $organicAgriculture->status);
        $this->assertSame('Not specified in the provided schedule.', $organicAgriculture->requirements);

        $psat = TrainingCenter::query()->where('type', 'PSAT')->sole();
        $this->assertSame('Alvear St. West, Lingayen, Pangasinan', $psat->address);
        $this->assertSame('075-662-2077', $psat->contact_phone);
        $this->assertDatabaseHas('training_programs', [
            'training_center_id' => $psat->id,
            'title' => 'Electrical Installation and Maintenance NC II',
            'duration_days' => 20,
        ]);
        $this->assertDatabaseHas('training_programs', [
            'training_center_id' => $psat->id,
            'title' => 'Electrical Installation and Maintenance NC II',
            'duration_days' => 25,
        ]);

        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->actingAs($admin)->get(route('centers.index'))
            ->assertSee($provincialCenter->name)
            ->assertSee($psat->name)
            ->assertSee('Organic Agriculture Production NC II')
            ->assertSee('29 training days');

        $this->getJson('/api/mobile/training-centers')->assertOk()->assertJsonFragment([
            'title' => 'Organic Agriculture Production NC II',
            'duration_days' => 29,
        ]);
    }

    public function test_source_listed_center_offerings_are_visible_to_career_seekers_without_claiming_open_enrollment(): void
    {
        $this->seed(PangasinanTrainingCenterSeeder::class);
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($youth)->get(route('centers.index'))
            ->assertSee('Organic Agriculture Production NC II')
            ->assertSee('29 training days');

        $this->get(route('tesda.index'))
            ->assertSee('Organic Agriculture Production NC II')
            ->assertSee('Courses listed by PTC and PSAT')
            ->assertSee('does not confirm an open intake')
            ->assertSee('Edson Technical Vocational Training and Assessment Center Inc.')
            ->assertSee('Ledesma School of Skills Development Inc.');

        $this->getJson('/api/mobile/tesda')->assertOk()
            ->assertJsonPath('pagination.total', 44);
    }

    public function test_career_seeker_mobile_catalog_shows_ptc_psat_duration_and_other_provider_fallbacks(): void
    {
        $this->seed(PangasinanTrainingCenterSeeder::class);
        $privateCenter = TrainingCenter::factory()->create(['type' => 'Private']);
        TrainingProgram::factory()->for($privateCenter, 'center')->create(['title' => 'Private-only test course']);
        $inactiveCenter = TrainingCenter::factory()->create(['type' => 'PSAT', 'is_active' => false]);
        TrainingProgram::factory()->for($inactiveCenter, 'center')->create(['title' => 'Inactive test course']);
        $demoCenter = TrainingCenter::factory()->create(['type' => 'PTC', 'is_demo' => true]);
        TrainingProgram::factory()->for($demoCenter, 'center')->create(['title' => 'Demo test course']);

        $response = $this->getJson('/api/mobile/tesda?career_seeker=1')->assertOk();
        $programs = $response->json('programs');
        $this->assertNotEmpty($programs);
        $this->assertNotContains('Private-only test course', array_column($programs, 'title'));
        $this->assertNotContains('Inactive test course', array_column($programs, 'title'));
        $this->assertNotContains('Demo test course', array_column($programs, 'title'));

        $response->assertJsonPath('pagination.total', 44)
            ->assertJsonFragment(['duration_days' => 29])
            ->assertJsonFragment(['provider' => 'Edson Technical Vocational Training and Assessment Center Inc.'])
            ->assertJsonFragment(['provider' => 'Ledesma School of Skills Development Inc.']);
    }

    public function test_tesda_role_keeps_the_existing_full_catalog_view(): void
    {
        $this->seed(PangasinanTrainingCenterSeeder::class);
        $tesda = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);

        $this->actingAs($tesda)->get(route('tesda.index'))
            ->assertSee('Training centers and registered programs')
            ->assertSee('Find your qualification')
            ->assertDontSee('Courses listed by PTC and PSAT');
    }
}
