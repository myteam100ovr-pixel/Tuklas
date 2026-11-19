<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouthProfileEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_rejects_birth_dates_outside_the_18_to_30_range(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth,
            'is_active' => true,
            'date_of_birth' => now()->subYears(20),
        ]);
        $user->youthProfile()->create([
            'interests' => ['Cooking'],
            'assessment_preferences' => ['career_goal' => 'Work in a bakery'],
        ]);

        $this->actingAs($user)->put(route('youth.profile.update'), [
            'date_of_birth' => now()->subYears(17)->toDateString(),
        ])->assertSessionHasErrors('date_of_birth');

        $this->assertSame(now()->subYears(20)->toDateString(), $user->fresh()->date_of_birth->toDateString());
    }

    public function test_profile_update_clears_legacy_guardian_fields_for_an_eligible_user(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth,
            'is_active' => true,
            'date_of_birth' => now()->subYears(20),
        ]);
        $user->youthProfile()->create([
            'interests' => ['Cooking'],
            'assessment_preferences' => ['career_goal' => 'Work in a bakery'],
            'guardian_name' => 'Legacy guardian',
            'guardian_relationship' => 'Parent',
            'guardian_contact' => '09000000000',
        ]);

        $this->actingAs($user)->put(route('youth.profile.update'), [
            'date_of_birth' => now()->subYears(20)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('youth_profiles', [
            'user_id' => $user->id,
            'guardian_name' => null,
            'guardian_relationship' => null,
            'guardian_contact' => null,
        ]);
    }
}
