<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\TesdaQualificationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TesdaChoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_youth_can_save_and_replace_a_current_qualification_plan(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $qualifications = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])
            ->where('status', 'current')->take(2)->values();

        $this->actingAs($youth)->post(route('tesda.choice.store'), [
            'qualification_id' => $qualifications[0]['id'],
        ])->assertRedirect();

        $this->assertDatabaseHas('tesda_choices', [
            'user_id' => $youth->id,
            'qualification_id' => $qualifications[0]['id'],
            'selected_by' => $youth->id,
        ]);

        $this->post(route('tesda.choice.store'), [
            'qualification_id' => $qualifications[1]['id'],
        ])->assertRedirect();

        $this->assertDatabaseCount('tesda_choices', 1);
        $this->assertDatabaseHas('tesda_choices', [
            'user_id' => $youth->id,
            'qualification_id' => $qualifications[1]['id'],
        ]);
    }

    public function test_staff_can_choose_for_a_youth_but_youth_cannot_choose_for_another_account(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $otherYouth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $qualification = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])
            ->firstWhere('status', 'current');

        $this->actingAs($youth)->post(route('tesda.choice.store'), [
            'qualification_id' => $qualification['id'],
            'user_id' => $otherYouth->id,
        ])->assertForbidden();

        $this->actingAs($trainer)->post(route('tesda.choice.store'), [
            'qualification_id' => $qualification['id'],
            'user_id' => $otherYouth->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tesda_choices', [
            'user_id' => $otherYouth->id,
            'qualification_id' => $qualification['id'],
            'selected_by' => $trainer->id,
        ]);
        $this->assertDatabaseMissing('tesda_choices', ['user_id' => $youth->id]);
    }

    public function test_archived_qualifications_cannot_be_saved(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $archived = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])
            ->firstWhere('status', 'archived');

        $this->actingAs($youth)->from(route('tesda.index'))->post(route('tesda.choice.store'), [
            'qualification_id' => $archived['id'],
        ])->assertRedirect(route('tesda.index'))->assertSessionHasErrors('qualification_id');

        $this->assertDatabaseCount('tesda_choices', 0);
    }

    public function test_super_admin_can_choose_for_youth_and_plan_is_visible_in_catalog(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $qualification = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])
            ->firstWhere('status', 'current');

        $this->actingAs($admin)->get(route('tesda.choices.index'))
            ->assertOk()->assertSee($youth->name);
        $this->post(route('tesda.choice.store'), [
            'qualification_id' => $qualification['id'],
            'user_id' => $youth->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tesda_choices', [
            'user_id' => $youth->id,
            'qualification_id' => $qualification['id'],
            'selected_by' => $admin->id,
        ]);
        $this->actingAs($youth)->get(route('tesda.index'))
            ->assertOk()->assertSee($qualification['title'])->assertSee('Chosen by '.$admin->name);
        $this->get(route('tesda.choices.index'))->assertForbidden();
    }
}
