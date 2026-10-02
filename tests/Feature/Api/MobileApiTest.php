<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_a_mobile_token_and_logout_revokes_it(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
            'password' => 'password-123',
        ]);

        $response = $this->postJson('/api/mobile/auth/token', [
            'email' => $user->email,
            'password' => 'password-123',
            'device_name' => 'Tuklas Android',
        ]);

        $response->assertOk()->assertJsonPath('user.role', Role::Youth->value);
        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Tuklas Android',
        ]);

        $this->withToken($token)->getJson('/api/mobile/me')->assertOk();
        $this->withToken($token)->deleteJson('/api/mobile/auth/token')->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Tuklas Android',
        ]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/mobile/me')->assertUnauthorized();
    }

    public function test_invalid_credentials_do_not_issue_a_token(): void
    {
        $user = User::factory()->create(['password' => 'password-123']);

        $this->postJson('/api/mobile/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'Tuklas iPhone',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_registration_creates_a_youth_account_and_returns_a_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/mobile/auth/register', [
            'name' => 'Mika Santos',
            'email' => 'mika@example.test',
            'password' => 'Long-password-123!',
            'password_confirmation' => 'Long-password-123!',
            'terms' => true,
            'device_name' => 'Tuklas Android',
        ]);

        $response->assertCreated()->assertJsonPath('user.role', Role::Youth->value);
        $user = User::where('email', 'mika@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_account_email_changes_require_verification_again(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/mobile/account', [
                'name' => 'Updated Name',
                'email' => 'updated@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'updated@example.test')
            ->assertJsonPath('user.email_verified', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
            'email_verified_at' => null,
        ]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_mobile_password_update_checks_current_password_and_keeps_current_token(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
            'password' => 'password-123',
        ]);
        $token = $user->createToken('Tuklas Android')->plainTextToken;

        $this->withToken($token)->putJson('/api/mobile/password', [
            'current_password' => 'wrong-password',
            'password' => 'New-password-123!',
            'password_confirmation' => 'New-password-123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->putJson('/api/mobile/password', [
            'current_password' => 'password-123',
            'password' => 'New-password-123!',
            'password_confirmation' => 'New-password-123!',
        ])->assertOk();

        $this->assertTrue(password_verify('New-password-123!', $user->fresh()->password));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Tuklas Android',
        ]);
    }

    public function test_mobile_password_recovery_sends_a_generic_response_and_resets_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);

        $this->postJson('/api/mobile/auth/forgot-password', ['email' => $user->email])
            ->assertAccepted()
            ->assertJsonPath('message', 'If an account exists for this email, a password reset link has been sent.');

        Notification::assertSentTo($user, ResetPassword::class);
        $resetNotification = Notification::sent($user, ResetPassword::class)->first();

        $this->postJson('/api/mobile/auth/reset-password', [
            'email' => $user->email,
            'token' => $resetNotification->token,
            'password' => 'New-password-123!',
            'password_confirmation' => 'New-password-123!',
        ])->assertOk();

        $this->assertTrue(password_verify('New-password-123!', $user->fresh()->password));
    }

    public function test_inactive_accounts_cannot_issue_mobile_tokens(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'password' => 'password-123',
        ]);

        $this->postJson('/api/mobile/auth/token', [
            'email' => $user->email,
            'password' => 'password-123',
            'device_name' => 'Tuklas Android',
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_youth_dashboard_returns_profile_scans_and_real_profile_breakdown(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);
        $user->youthProfile()->create([
            'educational_attainment' => 'Senior high school graduate',
            'skills' => ['Cooking'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/dashboard')
            ->assertOk()
            ->assertJsonPath('role', Role::Youth->value)
            ->assertJsonPath('stats.2.value', 1)
            ->assertJsonPath('chart.kind', 'meters')
            ->assertJsonPath('chart.items.1.label', 'Schooling');
    }

    public function test_trainer_dashboard_counts_only_the_trainers_programs(): void
    {
        $trainer = User::factory()->create([
            'role' => Role::Trainer->value,
            'is_active' => true,
        ]);
        $otherTrainer = User::factory()->create([
            'role' => Role::Trainer->value,
            'is_active' => true,
        ]);
        TrainingProgram::create([
            'title' => 'Trainer Published Course',
            'status' => 'published',
            'created_by' => $trainer->id,
        ]);
        TrainingProgram::create([
            'title' => 'Other Draft Course',
            'status' => 'draft',
            'created_by' => $otherTrainer->id,
        ]);

        $this->actingAs($trainer, 'sanctum')
            ->getJson('/api/mobile/dashboard')
            ->assertOk()
            ->assertJsonPath('role', Role::Trainer->value)
            ->assertJsonPath('stats.0.value', 1)
            ->assertJsonPath('stats.1.value', 1)
            ->assertJsonPath('recent.0.primary', 'Trainer Published Course');
    }

    public function test_admin_dashboard_returns_youth_trend_and_catalog_metrics(): void
    {
        User::factory()->count(2)->create([
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);
        User::factory()->create([
            'role' => Role::Trainer->value,
            'is_active' => true,
        ]);
        TrainingProgram::create(['title' => 'Published Program', 'status' => 'published']);

        $this->actingAs(User::factory()->create([
            'role' => Role::SuperAdmin->value,
            'is_active' => true,
        ]), 'sanctum')
            ->getJson('/api/mobile/dashboard')
            ->assertOk()
            ->assertJsonPath('role', Role::SuperAdmin->value)
            ->assertJsonPath('stats.0.value', 2)
            ->assertJsonPath('stats.1.value', 1)
            ->assertJsonPath('stats.2.value', 1)
            ->assertJsonCount(14, 'chart.items');
    }

    public function test_youth_profile_update_preserves_minor_guardian_requirements(): void
    {
        $dateOfBirth = now()->subYears(16)->toDateString();
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
            'date_of_birth' => $dateOfBirth,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/mobile/profile', [
                'date_of_birth' => $dateOfBirth,
                'barangay' => 'Poblacion',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guardian_name', 'guardian_relationship', 'guardian_contact']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/mobile/profile', [
                'date_of_birth' => $dateOfBirth,
                'barangay' => 'Poblacion',
                'guardian_name' => 'Maria Santos',
                'guardian_relationship' => 'Mother',
                'guardian_contact' => '09000000000',
            ])
            ->assertOk()
            ->assertJsonPath('profile.barangay', 'Poblacion');
    }

    public function test_tesda_mobile_catalog_only_exposes_published_programs(): void
    {
        TrainingProgram::create(['title' => 'Published Cooking', 'status' => 'published']);
        TrainingProgram::create(['title' => 'Draft Welding', 'status' => 'draft']);

        $this->getJson('/api/mobile/tesda')
            ->assertOk()
            ->assertJsonCount(1, 'programs')
            ->assertJsonPath('programs.0.title', 'Published Cooking');
    }

    public function test_dashboard_requires_a_verified_authenticated_account(): void
    {
        $this->getJson('/api/mobile/dashboard')->assertUnauthorized();

        $user = User::factory()->unverified()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/dashboard')
            ->assertForbidden();
    }

    public function test_two_factor_login_requires_and_consumes_a_recovery_code(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
            'password' => 'password-123',
        ]);
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['one-use-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $credentials = [
            'email' => $user->email,
            'password' => 'password-123',
            'device_name' => 'Tuklas Android',
        ];

        $this->postJson('/api/mobile/auth/token', $credentials)
            ->assertConflict()
            ->assertJsonPath('two_factor_required', true);

        $this->postJson('/api/mobile/auth/token', $credentials + ['recovery_code' => 'one-use-code'])
            ->assertOk()
            ->assertJsonPath('user.role', Role::Youth->value);

        $this->assertNotContains('one-use-code', $user->fresh()->recoveryCodes());
    }

    public function test_two_factor_can_be_enabled_and_confirmed_from_mobile(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
            'password' => 'password-123',
        ]);
        $token = $user->createToken('Tuklas Android')->plainTextToken;

        $setup = $this->withToken($token)->postJson('/api/mobile/security/two-factor', [
            'current_password' => 'password-123',
        ]);

        $setup->assertCreated()
            ->assertJsonStructure(['qr_code_url', 'manual_setup_key']);
        $this->assertNotEmpty($setup->json('manual_setup_key'));

        $provider = $this->mock(TwoFactorAuthenticationProvider::class);
        $provider->shouldReceive('verify')
            ->once()
            ->andReturn(true);

        $this->withToken($token)->postJson('/api/mobile/security/two-factor/confirm', [
            'code' => '123456',
        ])->assertOk()
            ->assertJsonPath('two_factor_enabled', true)
            ->assertJsonCount(8, 'recovery_codes');

        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_mobile_social_callback_exchanges_a_short_lived_ticket_once(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-youth-1',
            'name' => 'Mika Santos',
            'email' => 'mika.social@example.test',
        ]));

        $callback = $this->withSession(['mobile_social_login' => true])
            ->get('/auth/google/callback');
        $this->assertDatabaseHas('users', [
            'email' => 'mika.social@example.test',
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);
        $socialUser = User::where('email', 'mika.social@example.test')->firstOrFail();
        $this->assertSame(Role::Youth, $socialUser->role);
        $this->assertTrue($socialUser->hasRole(Role::Youth));
        $this->assertTrue($socialUser->is_active);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $socialUser->id,
            'provider' => 'google',
            'provider_id' => 'google-youth-1',
        ]);
        $callback->assertRedirect();
        $redirect = $callback->headers->get('Location');
        $this->assertIsString($redirect);
        $this->assertStringContainsString('tuklas://auth/social', $redirect);
        $this->assertStringContainsString('?ticket=', $redirect);
        $ticket = parse_url($redirect, PHP_URL_QUERY);
        parse_str(is_string($ticket) ? $ticket : '', $query);
        $this->assertArrayHasKey('ticket', $query);

        $payload = [
            'ticket' => $query['ticket'],
            'device_name' => 'Tuklas Android',
        ];
        $this->postJson('/api/mobile/auth/social/exchange', $payload)
            ->assertOk()
            ->assertJsonPath('user.email', 'mika.social@example.test');

        $this->postJson('/api/mobile/auth/social/exchange', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket');
    }

    public function test_mobile_social_ticket_requires_two_factor_for_protected_accounts(): void
    {
        $user = User::factory()->create([
            'role' => Role::Youth->value,
            'is_active' => true,
        ]);
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('test-secret'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['one-use-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $ticket = 'temporary-oauth-ticket';
        Cache::put('mobile-social-ticket:'.hash('sha256', $ticket), $user->id, now()->addMinutes(2));

        $this->postJson('/api/mobile/auth/social/exchange', [
            'ticket' => $ticket,
            'device_name' => 'Tuklas iPhone',
        ])->assertConflict()->assertJsonPath('two_factor_required', true);

        $this->postJson('/api/mobile/auth/social/exchange', [
            'ticket' => $ticket,
            'device_name' => 'Tuklas iPhone',
            'recovery_code' => 'one-use-code',
        ])->assertOk();
    }
}
