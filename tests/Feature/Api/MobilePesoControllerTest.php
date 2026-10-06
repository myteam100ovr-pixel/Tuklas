<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class MobilePesoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_youth_only_receive_their_own_completed_peso_matches(): void
    {
        $youth = $this->createUserWithRole(Role::Youth);
        $otherYouth = $this->createUserWithRole(Role::Youth);
        $theirScan = DocumentScan::factory()->for($youth)->create([
            'result' => [
                'jobRecommendations' => [['title' => 'Junior Web Developer']],
                'skillGaps' => ['Communication'],
                'learningRecommendations' => [['title' => 'Open course']],
            ],
        ]);
        DocumentScan::factory()->for($otherYouth)->create([
            'result' => ['jobRecommendations' => [['title' => 'Private match']]],
        ]);
        DocumentScan::factory()->for($youth)->create([
            'status' => 'failed',
            'result' => ['jobRecommendations' => [['title' => 'Failed match']]],
        ]);

        $this->actingAs($youth, 'sanctum')
            ->getJson(route('mobile.peso.index'))
            ->assertOk()
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.id', $theirScan->getKey())
            ->assertJsonPath('matches.0.job_recommendations.0.title', 'Junior Web Developer')
            ->assertJsonPath('matches.0.skill_gaps.0', 'Communication')
            ->assertJsonPath('matches.0.name', null)
            ->assertDontSee('Private match')
            ->assertDontSee('Failed match');
    }

    public function test_peso_admin_can_view_youth_matches_but_not_non_youth_matches(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youth = $this->createUserWithRole(Role::Youth, ['name' => 'Guidance Youth']);
        $trainer = $this->createUserWithRole(Role::Trainer);

        DocumentScan::factory()->for($youth)->create([
            'result' => ['jobRecommendations' => [['title' => 'Agricultural Technician']]],
        ]);
        DocumentScan::factory()->for($trainer)->create([
            'result' => ['jobRecommendations' => [['title' => 'Private trainer match']]],
        ]);

        $this->actingAs($administrator, 'sanctum')
            ->getJson(route('mobile.peso.index'))
            ->assertOk()
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.name', 'Guidance Youth')
            ->assertJsonPath('matches.0.job_recommendations.0.title', 'Agricultural Technician')
            ->assertDontSee('Private trainer match');
    }

    public function test_guests_cannot_read_mobile_peso_matches(): void
    {
        $this->getJson(route('mobile.peso.index'))->assertUnauthorized();
    }

    public function test_youth_dashboard_returns_recommendations_and_a_peso_action(): void
    {
        $youth = $this->createUserWithRole(Role::Youth);
        DocumentScan::factory()->for($youth)->create([
            'result' => [
                'jobRecommendations' => [['title' => 'Junior Web Developer']],
                'skillGaps' => ['Communication'],
                'learningRecommendations' => [['title' => 'Open course']],
            ],
        ]);

        $this->actingAs($youth, 'sanctum')
            ->getJson(route('mobile.dashboard'))
            ->assertOk()
            ->assertJsonPath('profileInsights.job_recommendations.0.title', 'Junior Web Developer')
            ->assertJsonPath('profileInsights.skill_gaps.0', 'Communication')
            ->assertJsonPath('profileInsights.learning_recommendations.0.title', 'Open course')
            ->assertJsonPath('actions.2.href', 'peso');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUserWithRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->role = $role;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
