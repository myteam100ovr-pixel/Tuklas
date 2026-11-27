<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\JobHiredNotification;
use App\Notifications\WorkspaceNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_and_web_requests_apply_only_supported_accept_language_values(): void
    {
        Route::get('/test/locale', fn () => response()->json(['locale' => app()->getLocale()]))->middleware('web');

        $this->withHeader('Accept-Language', 'tl-PH, en;q=0.8')
            ->getJson('/test/locale')
            ->assertOk()
            ->assertJsonPath('locale', 'tl');

        $this->withHeader('Accept-Language', 'tl;q=invalid, en;q=0.5, fr;q=1')
            ->postJson(route('mobile.auth.register'), [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.terms.0', 'The terms of service field is required.');
    }

    public function test_mobile_api_uses_a_valid_saved_locale_instead_of_accept_language(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'locale' => 'tl',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->getJson(route('mobile.me'))
            ->assertOk()
            ->assertJsonPath('user.locale', 'tl')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.role_label', 'Tagapangasiwa ng PESO Bugallon');
    }

    public function test_locale_only_mobile_account_update_persists_locale_without_weakening_profile_validation(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson(route('mobile.account.update'), ['locale' => 'tl'])
            ->assertOk()
            ->assertJsonPath('user.locale', 'tl')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.role_label', 'Tagapangasiwa ng PESO Bugallon');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => 'tl']);

        $this->putJson(route('mobile.account.update'), ['name' => 'New Name'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->putJson(route('mobile.account.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $user->email,
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'New Name')
            ->assertJsonPath('user.locale', 'en');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'locale' => 'en']);
    }

    public function test_web_locale_endpoint_saves_only_allowlisted_locales_and_requires_authentication(): void
    {
        $user = User::factory()->create(['locale' => 'en', 'is_active' => true]);

        $this->putJson(route('account.locale.update'), ['locale' => 'fr'])
            ->assertUnauthorized();

        $this->actingAs($user)
            ->withHeader('Accept-Language', 'tl-PH')
            ->putJson(route('account.locale.update'), ['locale' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.locale.0', 'The selected language is invalid.');

        $this->putJson(route('account.locale.update'), ['locale' => 'tl'])
            ->assertOk()
            ->assertJsonPath('locale', 'tl')
            ->assertJsonPath('message', 'Na-update ang piniling wika.');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => 'tl']);
    }

    public function test_invalid_saved_locale_is_never_applied_and_user_hydration_stays_allowlisted(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'locale' => 'fr', 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->withHeader('Accept-Language', 'tl-PH')
            ->getJson(route('mobile.me'))
            ->assertOk()
            ->assertJsonPath('user.locale', 'en')
            ->assertJsonPath('user.role_label', 'Tagapangasiwa ng PESO Bugallon');

        $this->assertSame('en', $user->fresh()->preferredLocale());
    }

    public function test_workspace_and_hiring_notifications_use_each_recipients_saved_locale(): void
    {
        $user = User::factory()->create(['locale' => 'tl']);
        $notice = new WorkspaceNotice(
            'PESO recorded your hiring',
            'You were marked as hired for Cook at Example Hotel.',
            'hiring',
        );

        $payload = $notice->toDatabase($user);

        $this->assertSame('Naitala ng PESO ang iyong pagkakatanggap sa trabaho', $payload['title']);
        $this->assertSame('Itinala na natanggap ka sa trabaho bilang Cook sa Example Hotel.', $payload['message']);
        $this->assertSame('hiring', $payload['kind']);

        $mail = (new JobHiredNotification('Cook', 'Example Hotel'))->toMail($user);

        $this->assertSame('Naitala ng PESO ang iyong pagkakatanggap sa trabaho', $mail->subject);
        $this->assertSame('Binabati kita!', $mail->greeting);
        $this->assertSame('Itinala ng PESO na natanggap ka sa trabaho bilang Cook sa Example Hotel.', $mail->introLines[0]);
    }
}
