<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['youth', 'youth.dashboard'])]
    #[TestWith(['trainer', 'trainer.dashboard'])]
    #[TestWith(['super_admin', 'admin.dashboard'])]
    public function test_each_demo_role_opens_its_own_dashboard(string $role, string $dashboard): void
    {
        $this->post(route('demo.login', $role))->assertRedirect(route($dashboard));

        $user = User::query()->where('email', 'demo-'.$role.'@tuklas.test')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($role, $user->role->value);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_demo_accounts_are_shown_on_auth_pages(): void
    {
        foreach ([route('register'), route('login')] as $url) {
            $this->get($url)
                ->assertSee('demo-youth@tuklas.test')
                ->assertSee('demo-trainer@tuklas.test')
                ->assertSee('demo-super_admin@tuklas.test');
        }
    }

    public function test_demo_login_is_unavailable_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        $this->withoutMiddleware(PreventRequestForgery::class);

        $this->post(route('demo.login', 'youth'))->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_login_refuses_an_existing_account_with_the_wrong_role(): void
    {
        User::factory()->create([
            'email' => 'demo-youth@tuklas.test',
            'role' => Role::Trainer,
        ]);

        $this->post(route('demo.login', 'youth'))->assertForbidden();
        $this->assertGuest();
    }
}
