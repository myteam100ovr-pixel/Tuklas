<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class CareerRecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_can_view_recommendations_with_supporting_context(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youth = $this->createUserWithRole(Role::Youth, [
            'name' => 'Job Match Youth',
            'email' => 'private-youth@example.test',
        ]);

        DocumentScan::factory()->for($youth)->create([
            'doc_type' => 'resume',
            'result' => [
                'jobRecommendations' => [[
                    'title' => 'Junior Web Developer',
                    'reason' => 'The resume lists web development projects.',
                    'evidence' => 'HTML, CSS, and JavaScript',
                ]],
            ],
        ]);

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('PESO job matches')
            ->assertSee('Job Match Youth')
            ->assertSee('Resume')
            ->assertSee('Junior Web Developer')
            ->assertSee('The resume lists web development projects.')
            ->assertSee('HTML, CSS, and JavaScript')
            ->assertDontSee('private-youth@example.test')
            ->assertSee('career guidance')
            ->assertSee('PESO')
            ->assertSee('AI Scanner');
    }

    public function test_only_completed_youth_scans_with_recommendations_are_listed(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youthWithMatches = $this->createUserWithRole(Role::Youth, ['name' => 'Eligible Youth']);
        $youthWithoutMatches = $this->createUserWithRole(Role::Youth, ['name' => 'No Matches Youth']);
        $trainer = $this->createUserWithRole(Role::Trainer, ['name' => 'Trainer Account']);

        DocumentScan::factory()->for($youthWithMatches)->create([
            'result' => ['jobRecommendations' => [['title' => 'Agricultural Technician']]],
        ]);
        DocumentScan::factory()->for($youthWithMatches)->create([
            'status' => 'failed',
            'result' => ['jobRecommendations' => [['title' => 'Failed Scan Role']]],
        ]);
        DocumentScan::factory()->for($youthWithoutMatches)->create([
            'result' => ['summary' => 'No career suggestions were returned.'],
        ]);
        DocumentScan::factory()->for($trainer)->create([
            'result' => ['jobRecommendations' => [['title' => 'Trainer Role']]],
        ]);

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('Eligible Youth')
            ->assertSee('Agricultural Technician')
            ->assertDontSee('Failed Scan Role')
            ->assertDontSee('No Matches Youth')
            ->assertDontSee('Trainer Account')
            ->assertDontSee('Trainer Role');
    }

    public function test_non_admin_users_cannot_view_youth_recommendations(): void
    {
        $trainer = $this->createUserWithRole(Role::Trainer);

        $this->actingAs($trainer)
            ->get(route('admin.recommendations.index'))
            ->assertForbidden();
    }

    public function test_guest_users_are_redirected_to_login(): void
    {
        $this->get(route('admin.recommendations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_youth_names_and_recommendation_text_are_escaped(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youth = $this->createUserWithRole(Role::Youth, [
            'name' => '<script>alert("name")</script>',
        ]);

        DocumentScan::factory()->for($youth)->create([
            'result' => [
                'jobRecommendations' => [[
                    'title' => '<script>alert("title")</script>',
                    'reason' => '<script>alert("reason")</script>',
                ]],
            ],
        ]);

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("name")</script>', false)
            ->assertDontSee('<script>alert("title")</script>', false)
            ->assertDontSee('<script>alert("reason")</script>', false);
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
