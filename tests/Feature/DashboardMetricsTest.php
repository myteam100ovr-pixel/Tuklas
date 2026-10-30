<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\ProfileCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_profile_uses_field_counts_on_web_and_mobile(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'date_of_birth' => now()->subYears(20)]);
        $user->youthProfile()->create(['barangay' => 'Sample', 'educational_attainment' => 'College graduate', 'interests' => ['Agriculture'], 'credentials' => ['A certificate']]);
        $completion = ProfileCompletion::for($user->fresh());
        $this->assertSame(36, $completion['percent']);
        $this->assertSame(4, $completion['completed']);
        $this->assertSame(11, $completion['total']);
        $this->assertSame([67, 50, 33, 0], array_column($completion['items'], 'pct'));
        $this->actingAs($user)->get(route('youth.dashboard'))->assertOk()->assertSee('36%')->assertSee('4 of 11 details added');
        $this->getJson(route('mobile.dashboard'))->assertOk()->assertJsonPath('stats.0.value', '36%');
    }

    public function test_complete_profile_does_not_require_credentials_and_minor_guardian_fields_count(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'date_of_birth' => now()->subYears(16)]);
        $user->youthProfile()->create([
            'barangay' => 'Sample', 'contact_number' => '09000000000',
            'educational_attainment' => 'Senior high school undergraduate', 'employment_status' => 'Student',
            'interests' => ['Agriculture'],
            'assessment_preferences' => ['priority' => 'Explore my options', 'career_goal' => 'Learn farming', 'learning_mode' => 'Online at my own pace', 'schedule' => 'Weekends', 'weekly_hours' => '5–10 hours'],
        ]);
        $this->assertSame(79, ProfileCompletion::for($user->fresh())['percent']);
        $user->youthProfile()->update(['guardian_name' => 'Guardian', 'guardian_relationship' => 'Parent', 'guardian_contact' => '09000000001']);
        $this->assertSame(100, ProfileCompletion::for($user->fresh())['percent']);
        $this->actingAs($user)->get(route('youth.dashboard'))->assertViewHas('tasks', fn (array $tasks): bool => $tasks['items'][2]['state'] === 'done');
    }

    public function test_empty_profile_and_empty_admin_chart_have_true_zero_values(): void
    {
        $user = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $this->assertSame(0, ProfileCompletion::for($user)['percent']);
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('chart', fn (array $chart): bool => array_sum(array_column($chart['bars'], 'h')) === 0);
        $this->getJson(route('mobile.dashboard'))->assertOk()->assertJsonPath('chart.bars.0.h', 0);
    }

    public function test_trainer_percentages_use_owned_programs_and_explain_empty_data(): void
    {
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $this->actingAs($trainer)->get(route('trainer.dashboard'))->assertOk()->assertSee('No data yet');
        foreach (['published', 'published', 'draft', 'archived'] as $status) {
            TrainingProgram::create(['title' => 'Program', 'status' => $status, 'created_by' => $trainer->id]);
        }
        TrainingProgram::create(['title' => 'Someone else', 'status' => 'published']);
        $this->get(route('trainer.dashboard'))->assertOk()
            ->assertViewHas('chart', fn (array $chart): bool => array_column($chart['items'], 'pct') === [50, 25, 25]);
        $this->getJson(route('mobile.dashboard'))->assertOk()->assertJsonPath('chart.items.0.pct', 50);
    }
}
