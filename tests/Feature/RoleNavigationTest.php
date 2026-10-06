<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function workspaceRoles(): array
    {
        return [
            'PESO administrator' => [Role::SuperAdmin, 'admin.dashboard', true],
            'trainer' => [Role::Trainer, 'trainer.dashboard', false],
            'youth' => [Role::Youth, 'youth.dashboard', false],
        ];
    }

    #[DataProvider('workspaceRoles')]
    public function test_each_role_has_the_horizontal_peso_and_ai_scanner_navigation(
        Role $role,
        string $dashboardRoute,
        bool $isPESOAdmin
    ): void {
        $user = User::factory()->create();
        $user->role = $role;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        $response = $this->actingAs($user)
            ->get(route($dashboardRoute))
            ->assertOk()
            ->assertSee('PESO')
            ->assertSee('AI Scanner')
            ->assertSee('tk-tabs--workspace')
            ->assertSee('href="'.route('peso.index').'"', false)
            ->assertSee('href="'.route('scanner.index').'"', false);

        if ($isPESOAdmin) {
            $response->assertSee('People');
        } else {
            $response->assertDontSee('People');
        }
    }

    public function test_youth_dashboard_shows_a_job_role_from_their_latest_scan(): void
    {
        $youth = User::factory()->create();
        $youth->role = Role::Youth;
        $youth->is_active = true;
        $youth->email_verified_at = now();
        $youth->save();

        DocumentScan::factory()->for($youth)->create([
            'result' => ['job_roles' => ['Community Health Worker']],
        ]);

        $this->actingAs($youth)
            ->get(route('youth.dashboard'))
            ->assertOk()
            ->assertSee('Your career insights')
            ->assertSee('Suggested job roles')
            ->assertSee('AI job matches')
            ->assertSee('Community Health Worker');
    }

    public function test_youth_overview_shows_skill_gaps_and_open_learning_resources_from_their_scan(): void
    {
        $youth = User::factory()->create();
        $youth->role = Role::Youth;
        $youth->is_active = true;
        $youth->email_verified_at = now();
        $youth->save();

        DocumentScan::factory()->for($youth)->create([
            'result' => [
                'jobRecommendations' => [[
                    'title' => 'Junior Web Developer',
                    'reason' => 'Your scan shows basic web skills.',
                    'evidence' => 'HTML and CSS',
                ]],
                'skillGaps' => ['JavaScript fundamentals'],
                'learningRecommendations' => [[
                    'title' => 'JavaScript Algorithms and Data Structures',
                    'reason' => 'Practice JavaScript fundamentals with free lessons.',
                    'learningSite' => 'freeCodeCamp (freecodecamp.org)',
                    'directUrl' => 'https://www.freecodecamp.org/learn/',
                ]],
            ],
        ]);

        $this->actingAs($youth)
            ->get(route('youth.dashboard'))
            ->assertOk()
            ->assertSee('Junior Web Developer')
            ->assertSee('JavaScript fundamentals')
            ->assertSee('Free learning and open-source practice')
            ->assertSee('freeCodeCamp (freecodecamp.org)')
            ->assertSee('href="https://www.freecodecamp.org/learn/"', false);
    }

    public function test_non_https_learning_links_are_not_rendered_as_clickable_urls(): void
    {
        $youth = User::factory()->create();
        $youth->role = Role::Youth;
        $youth->is_active = true;
        $youth->email_verified_at = now();
        $youth->save();

        DocumentScan::factory()->for($youth)->create([
            'result' => [
                'learningRecommendations' => [[
                    'title' => 'Unsafe link test',
                    'directUrl' => 'javascript:alert(1)',
                ]],
            ],
        ]);

        $this->actingAs($youth)
            ->get(route('youth.dashboard'))
            ->assertOk()
            ->assertSee('Unsafe link test')
            ->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_peso_job_matches_show_only_the_signed_in_users_scans_to_non_admins(): void
    {
        $youth = User::factory()->create();
        $youth->role = Role::Youth;
        $youth->is_active = true;
        $youth->email_verified_at = now();
        $youth->save();
        $otherYouth = User::factory()->create();
        $otherYouth->role = Role::Youth;
        $otherYouth->is_active = true;
        $otherYouth->email_verified_at = now();
        $otherYouth->save();

        DocumentScan::factory()->for($youth)->create([
            'original_name' => 'my-resume.pdf',
            'result' => ['jobRecommendations' => [['title' => 'My Career Match']]],
        ]);
        DocumentScan::factory()->for($otherYouth)->create([
            'original_name' => 'other-resume.pdf',
            'result' => ['jobRecommendations' => [['title' => 'Private Career Match']]],
        ]);

        $this->actingAs($youth)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('My Career Match')
            ->assertSee('my-resume.pdf')
            ->assertDontSee('Private Career Match')
            ->assertDontSee('other-resume.pdf');
    }
}
