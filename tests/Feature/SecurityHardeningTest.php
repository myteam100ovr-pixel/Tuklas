<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_user_cannot_sign_in_on_the_web(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    #[TestWith(['/user/profile'])]
    #[TestWith(['/api/user'])]
    public function test_disabled_sessions_cannot_access_account_routes(string $path): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->getJson($path)->assertForbidden();
    }

    public function test_disabled_user_can_still_log_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    #[TestWith(['/login'])]
    #[TestWith(['/api/mobile/auth/token'])]
    public function test_array_email_is_rejected_without_server_error(string $path): void
    {
        $this->postJson($path, ['email' => ['bad'], 'password' => 'password', 'device_name' => 'test'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_mobile_token_response_cannot_be_cached(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        $this->postJson('/api/mobile/auth/token', [
            'email' => $user->email, 'password' => 'password', 'device_name' => 'test',
        ])->assertOk()->assertJsonStructure(['token'])->assertHeader('Cache-Control', 'no-store, private');
    }

    #[TestWith(['/reset-password'])]
    #[TestWith(['/api/mobile/auth/reset-password'])]
    public function test_password_recovery_revokes_tokens_and_remember_cookie(string $path): void
    {
        $user = User::factory()->create(['remember_token' => 'old-cookie', 'admin_password_reset_requested_at' => now()]);
        $user->createToken('lost-device');
        $token = Password::createToken($user);
        $this->postJson($path, [
            'email' => $user->email, 'token' => $token,
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotSame('old-cookie', $user->fresh()->remember_token);
        $this->assertNull($user->fresh()->admin_password_reset_requested_at);
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_web_password_change_revokes_mobile_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        $user->createToken('old-device');
        $this->actingAs($user)->putJson('/user/password', [
            'current_password' => 'password', 'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_mobile_password_change_preserves_only_the_current_token(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        $current = $user->createToken('current-device');
        $user->createToken('old-device');
        $this->withToken($current->plainTextToken)->putJson('/api/mobile/password', [
            'current_password' => 'password', 'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();
        $this->assertSame([$current->accessToken->id], $user->tokens()->pluck('id')->all());
    }

    public function test_admin_requested_password_reset_revokes_existing_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth, 'admin_password_reset_requested_at' => now()]);
        $user->createToken('old-device');
        $this->actingAs($user)->putJson('/api/mobile/account/password-reset', [
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNull($user->fresh()->admin_password_reset_requested_at);
    }

    #[TestWith(['deactivate'])]
    #[TestWith(['role'])]
    #[TestWith(['delete'])]
    public function test_account_access_changes_revoke_existing_tokens(string $change): void
    {
        $user = User::factory()->create(['role' => Role::Youth]);
        $token = $user->createToken('old-device');
        match ($change) {
            'deactivate' => $user->forceFill(['is_active' => false])->save(),
            'role' => $user->forceFill(['role' => Role::Trainer])->save(),
            'delete' => $user->delete(),
        };
        $this->assertSame(0, $user->tokens()->count());
        $this->withToken($token->plainTextToken)->getJson('/api/mobile/me')->assertUnauthorized();
    }

    #[TestWith(['PUT', '/api/mobile/password'])]
    #[TestWith(['POST', '/api/mobile/security/two-factor'])]
    #[TestWith(['POST', '/api/mobile/security/two-factor/confirm'])]
    #[TestWith(['POST', '/api/mobile/security/two-factor/recovery-codes'])]
    #[TestWith(['DELETE', '/api/mobile/security/two-factor'])]
    public function test_mobile_security_attempts_are_rate_limited(string $method, string $path): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->json($method, $path)->assertUnprocessable();
        }
        $this->json($method, $path)->assertTooManyRequests();
    }
}
