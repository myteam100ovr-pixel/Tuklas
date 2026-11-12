<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WorkspaceSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['GET', '/dashboard'])]
    #[TestWith(['GET', '/scanner'])]
    #[TestWith(['GET', '/document-scans'])]
    #[TestWith(['GET', '/peso'])]
    #[TestWith(['POST', '/career-chat'])]
    #[TestWith(['POST', '/document-scans'])]
    public function test_disabled_accounts_cannot_use_shared_workspace_routes(string $method, string $path): void
    {
        $user = User::factory()->create(['is_active' => false, 'role' => Role::Youth]);
        $this->actingAs($user)->json($method, $path)->assertForbidden();
    }

    #[TestWith(['youth'])]
    #[TestWith(['tesda_admin'])]
    public function test_non_admin_roles_cannot_read_the_user_directory(string $role): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::from($role)]);
        $this->actingAs($user)->get('/admin/users')->assertForbidden()->assertSee('Access unavailable');
    }

    public function test_signed_in_scanner_responses_are_private_and_protected(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        $this->actingAs($user)->get('/scanner')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/scanner')->assertRedirect(route('login'));
    }

    #[TestWith([403, 'Access unavailable'])]
    #[TestWith([404, 'Page not found'])]
    #[TestWith([419, 'Your session expired'])]
    #[TestWith([429, 'A little too many requests'])]
    #[TestWith([500, 'Something went wrong'])]
    #[TestWith([503, 'Something went wrong'])]
    public function test_error_pages_offer_recovery_without_leaking_exception_details(int $status, string $heading): void
    {
        config(['app.debug' => false]);
        Route::get('/test-workspace-error', fn () => abort($status, 'Internal secret details'));
        $this->get('/test-workspace-error')->assertStatus($status)
            ->assertSee($heading)->assertSee('Return to home')->assertDontSee('Internal secret details');
    }
}
