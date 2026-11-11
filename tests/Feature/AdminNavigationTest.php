<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_page_shows_group_tabs_sidebar_account_and_chat_link(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('All users')
            ->assertDontSee('Employers')
            ->assertSee('tk-admin-page-tabs', false)
            ->assertSee('class="tk-avatar"', false)
            ->assertSee('Account menu for', false)
            ->assertSee('Profile')
            ->assertSee('Logout')
            ->assertSee('tk-admin-chat-button', false)
            ->assertSee(route('tuklasbot.index'), false)
            ->assertDontSee('Manage users');
    }

    #[TestWith(['opportunities.index', 'Openings'])]
    #[TestWith(['peso.index', 'PESO'])]
    #[TestWith(['centers.index', 'Centers'])]
    #[TestWith(['center-trainers.index', 'Trainers'])]
    #[TestWith(['sessions.index', 'Dates'])]
    #[TestWith(['applications.index', 'Requests'])]
    #[TestWith(['assessments.index', 'Results'])]
    #[TestWith(['scholarships.index', 'Scholarships'])]
    #[TestWith(['tesda.index', 'TESDA'])]
    #[TestWith(['messages.index', 'Messages'])]
    #[TestWith(['notifications.index', 'Notifications'])]
    public function test_group_tab_keeps_its_original_page_and_marks_it_active(string $routeName, string $tabLabel): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);

        $this->actingAs($admin)->get(route($routeName))
            ->assertOk()
            ->assertSee($tabLabel)
            ->assertSee('tk-admin-page-tab is-active', false);
    }

    #[TestWith(['/opportunities', 'opportunities.index'])]
    #[TestWith(['/peso', 'peso.index'])]
    #[TestWith(['/scholarships', 'scholarships.index'])]
    #[TestWith(['/training-centers', 'centers.index'])]
    #[TestWith(['/center-trainers', 'center-trainers.index'])]
    #[TestWith(['/training-sessions', 'sessions.index'])]
    #[TestWith(['/training-applications', 'applications.index'])]
    #[TestWith(['/assessment-records', 'assessments.index'])]
    #[TestWith(['/messages', 'messages.index'])]
    #[TestWith(['/notifications', 'notifications.index'])]
    #[TestWith(['/admin/employers', 'admin.users.index'])]
    #[TestWith(['/tesda', 'tesda.index'])]
    public function test_legacy_page_url_redirects_to_its_grouped_tab(string $legacyUrl, string $routeName): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);

        $this->actingAs($admin)->get($legacyUrl)->assertRedirect(route($routeName));
    }

    #[TestWith(['/center-trainers'])]
    #[TestWith(['/admin/training/trainers'])]
    public function test_training_trainer_tab_keeps_its_existing_role_restriction(string $path): void
    {
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);

        $this->actingAs($trainer)->get($path)->assertForbidden();
    }
}
