<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_landing_page_with_auth_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AI-Powered Career Path and')
            ->assertSee('Youth in Pangasinan')
            ->assertSee(route('login'))
            ->assertSee(route('register'));
    }

    public function test_landing_page_shows_gemini_scan_and_tech_stack(): void
    {
        $this->get('/')
            ->assertSee('Powered by Google Gemini AI')
            ->assertSee('Scan Your Resume')
            ->assertSee('Laravel')
            ->assertSee('Livewire');
    }

    public function test_landing_page_has_no_free_trial_wording(): void
    {
        $this->get('/')
            ->assertDontSee('Free Trial', false)
            ->assertDontSee('free trial', false)
            ->assertDontSee('Free Account', false);
    }

    public function test_landing_page_states_the_guidance_disclaimer(): void
    {
        $this->get('/')->assertSee('not guarantees of employment, admission, or training availability');
    }

    public function test_signed_in_user_sees_dashboard_link(): void
    {
        $user = User::forceCreate([
            'name' => 'Test Youth', 'email' => 'youth@example.test',
            'password' => bcrypt('password-123'), 'role' => Role::Youth->value,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/')->assertOk()->assertSee(route('dashboard'));
    }
}
