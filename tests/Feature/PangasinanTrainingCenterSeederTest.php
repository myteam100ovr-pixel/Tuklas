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
        $this->assertSame('draft', $organicAgriculture->status);
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

        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
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

    public function test_unpublished_center_offerings_stay_hidden_from_career_seekers_until_published(): void
    {
        $this->seed(PangasinanTrainingCenterSeeder::class);
        $program = TrainingProgram::query()->where('title', 'Organic Agriculture Production NC II')->firstOrFail();
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($youth)->get(route('centers.index'))
            ->assertDontSee('Organic Agriculture Production NC II');

        $program->update(['status' => 'published']);

        $this->get(route('centers.index'))
            ->assertSee('Organic Agriculture Production NC II')
            ->assertSee('29 training days');

        $this->getJson('/api/mobile/tesda')->assertOk()->assertJsonFragment([
            'title' => 'Organic Agriculture Production NC II',
            'duration_days' => 29,
            'training_center' => 'PROVINCIAL TRAINING CENTER - PANGASINAN (PUBLIC SCHOOL)',
        ]);
    }
}
