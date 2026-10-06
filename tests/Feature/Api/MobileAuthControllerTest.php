<?php

namespace Tests\Feature\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Event;

class MobileAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_registration_creates_an_account_that_can_sign_in_on_the_web(): void
    {
        Event::fake([Registered::class]);

        $response = $this->postJson(route('mobile.auth.register'), [
            'name' => 'Mobile Youth',
            'email' => 'mobile-youth@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'device_name' => 'tuklas-mobile',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Account created. Verify your email address before signing in.')
            ->assertJsonPath('user.email', 'mobile-youth@example.com')
            ->assertJsonPath('user.role', 'youth');

        $this->assertArrayNotHasKey('token', $response->json());
        Event::assertDispatched(Registered::class);

        $user = User::query()->where('email', 'mobile-youth@example.com')->sole();
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'email' => 'mobile-youth@example.com',
            'role' => 'youth',
        ]);

        $this->post(route('login'), [
            'email' => 'mobile-youth@example.com',
            'password' => 'StrongPassword123!',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_mobile_registration_rejects_an_email_already_registered_on_the_web(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson(route('mobile.auth.register'), [
            'name' => 'Duplicate Youth',
            'email' => 'existing@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'device_name' => 'tuklas-mobile',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_mobile_login_accepts_credentials_created_by_the_shared_web_registration_action(): void
    {
        $user = app(CreateNewUser::class)->create([
            'name' => 'Web Youth',
            'email' => 'web-youth@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $response = $this->postJson(route('mobile.auth.token'), [
            'email' => 'web-youth@example.com',
            'password' => 'StrongPassword123!',
            'device_name' => 'tuklas-mobile',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->getKey())
            ->assertJsonPath('user.email', 'web-youth@example.com');

        $this->getJson(route('mobile.me'), [
            'Authorization' => 'Bearer '.$response->json('token'),
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->getKey());
    }
}
