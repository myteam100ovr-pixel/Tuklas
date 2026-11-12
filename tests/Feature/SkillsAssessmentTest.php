<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillsAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_youth_can_open_assessment_and_save_without_losing_existing_skills(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $user->youthProfile()->create(['skills' => ['HTML'], 'credentials' => ['Existing certificate']]);

        $this->actingAs($user)->get(route('youth.dashboard'))->assertOk()
            ->assertSee(route('youth.assessment.index'))->assertDontSee('Coming soon');
        $this->get(route('youth.assessment.index'))->assertOk()->assertSee('Skills self-assessment');

        $this->post(route('youth.assessment.store'), [
            'skills' => ['Communication'],
            'interests' => ['Information technology'],
        ])->assertRedirectToRoute('youth.assessment.index')->assertSessionHas('status');

        $profile = $user->fresh()->youthProfile;
        $this->assertSame(['HTML', 'Communication'], $profile->skills);
        $this->assertSame(['Information technology'], $profile->interests);
        $this->assertSame(['Existing certificate'], $profile->credentials);
    }

    public function test_beginner_can_submit_interests_without_claiming_skills(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($user)->post(route('youth.assessment.store'), [
            'interests' => ['Agriculture'],
        ])->assertRedirectToRoute('youth.assessment.index');
        $this->assertSame([], $user->fresh()->youthProfile->skills);
    }

    public function test_invalid_assessment_does_not_update_profile(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($user)->postJson(route('youth.assessment.store'), [
            'skills' => ['Invented competency'],
            'interests' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['skills.0', 'interests']);
        $this->assertDatabaseCount('youth_profiles', 0);
    }

    public function test_guests_and_trainers_cannot_submit_youth_assessments(): void
    {
        $this->postJson(route('youth.assessment.store'), [])->assertUnauthorized();
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $this->actingAs($trainer)->postJson(route('youth.assessment.store'), [])->assertForbidden();
        $this->assertDatabaseCount('youth_profiles', 0);
    }

    public function test_updated_goals_replace_old_preferences_and_are_restored_in_the_form(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $user->youthProfile()->create([
            'interests' => ['Agriculture'],
            'assessment_preferences' => ['career_goal' => 'Old goal', 'support_needs' => ['Scholarship information']],
        ]);

        $this->actingAs($user)->post(route('youth.assessment.store'), [
            'interests' => ['Food and beverage'],
            'career_goal' => 'I want to open a bakery',
            'learning_mode' => 'Hands-on / face-to-face',
            'schedule' => 'Weekends',
            'weekly_hours' => '5–10 hours',
        ])->assertRedirectToRoute('youth.assessment.index');

        $profile = $user->fresh()->youthProfile;
        $this->assertSame(['Food and beverage'], $profile->interests);
        $this->assertSame('I want to open a bakery', $profile->assessment_preferences['career_goal']);
        $this->assertArrayNotHasKey('support_needs', $profile->assessment_preferences);
        $this->get(route('youth.assessment.index'))->assertOk()->assertSee('I want to open a bakery');
    }

    public function test_invalid_preferences_are_rejected_without_saving(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($user)->postJson(route('youth.assessment.store'), [
            'interests' => ['Agriculture'],
            'learning_mode' => 'Unknown format',
            'career_goal' => str_repeat('a', 501),
            'support_needs' => ['Guaranteed admission'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['learning_mode', 'career_goal', 'support_needs.0']);
        $this->assertDatabaseCount('youth_profiles', 0);
    }
}
