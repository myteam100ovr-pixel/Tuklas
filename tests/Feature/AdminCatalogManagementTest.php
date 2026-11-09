<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CareerPath;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_shortcuts_open_catalog_management_pages(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee(route('admin.careers.index'))
            ->assertSee(route('admin.trainings.index'))
            ->assertDontSee('Coming soon');
        $this->get(route('admin.careers.index'))->assertOk()->assertSee('Add career path');
        $this->get(route('admin.trainings.index'))->assertOk()->assertSee('Add program');
    }

    public function test_admin_can_publish_and_archive_career_paths_seen_by_members(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.careers.store'), [
            'title' => 'Agricultural technician',
            'description' => 'Support local farms.',
            'education_level' => 'Senior high school',
            'source_note' => 'Local career guide',
            'status' => 'published',
        ])->assertRedirect(route('admin.careers.index'));

        $career = CareerPath::query()->where('title', 'Agricultural technician')->sole();
        $this->assertSame('published', $career->status);
        $this->actingAs($youth)->get(route('careers.index'))->assertOk()
            ->assertSee('Agricultural technician')->assertSee('Support local farms.');

        $this->actingAs($admin)->put(route('admin.careers.update', $career), [
            'title' => $career->title,
            'status' => 'archived',
        ])->assertRedirect(route('admin.careers.index'));
        $this->assertSame('archived', $career->fresh()->status);
        $this->actingAs($youth)->get(route('careers.index'))->assertDontSee('Agricultural technician');
    }

    public function test_admin_can_manage_training_without_transferring_trainer_ownership(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.trainings.store'), [
            'title' => 'Community baking', 'nc_level' => 'NC II', 'status' => 'draft',
        ])->assertRedirect(route('admin.trainings.index'));
        $adminProgram = TrainingProgram::query()->where('title', 'Community baking')->sole();
        $this->assertSame($admin->id, $adminProgram->created_by);
        $this->assertSame('draft', $adminProgram->status);

        $program = TrainingProgram::factory()->create(['title' => 'Local cookery', 'created_by' => $trainer->id, 'status' => 'draft']);

        $this->actingAs($admin)->get(route('admin.trainings.index'))->assertOk()->assertSee('Local cookery')->assertSee($trainer->name);
        $this->put(route('admin.trainings.update', $program), [
            'title' => 'Local cookery', 'nc_level' => 'NC II', 'status' => 'published',
        ])->assertRedirect(route('admin.trainings.index'));
        $this->assertSame($trainer->id, $program->fresh()->created_by);
        $this->assertNotNull($program->fresh()->last_verified_at);
        $this->get(route('tesda.index'))->assertSee('Local cookery');

        $this->actingAs($trainer)->get(route('trainer.programs.edit', $program))->assertOk();
    }

    public function test_non_admins_cannot_manage_catalogs_and_invalid_data_is_rejected(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->get(route('admin.careers.index'))->assertForbidden();
        $this->post(route('admin.trainings.store'), ['title' => 'Course', 'status' => 'published'])->assertForbidden();

        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.careers.store'), ['title' => '', 'status' => 'public'])
            ->assertSessionHasErrors(['title', 'status']);
        $this->post(route('admin.trainings.store'), ['title' => 'Course', 'nc_level' => 'NC V', 'status' => 'published'])
            ->assertSessionHasErrors('nc_level');
        $this->assertDatabaseCount('career_paths', 0);
        $this->assertDatabaseCount('training_programs', 0);
    }
}
