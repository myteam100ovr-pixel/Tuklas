<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_gets_a_gemini_reply_from_flash_37(): void
    {
        config([
            'services.google.gemini_api_key' => 'test-key',
            'services.google.gemini_model' => 'gemini-3.7-flash',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Explore training that matches your interests.']]],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['role' => 'youth', 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'What should I explore?']],
        ]);

        $response->assertOk()->assertJsonPath('reply', 'Explore training that matches your interests.');
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/models/gemini-3.7-flash:generateContent')
            && data_get($request->data(), 'contents.0.parts.0.text') === 'What should I explore?');
    }

    public function test_guest_is_redirected_to_login_when_sending_a_chat_message(): void
    {
        $this->post(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Help me explore careers.']],
        ])->assertRedirect(route('login'));
    }

    public function test_empty_chat_history_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'youth']);

        $this->actingAs($user)
            ->postJson(route('career-chat.store'), ['messages' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('messages');
    }

    public function test_chat_is_unavailable_without_a_gemini_api_key(): void
    {
        config(['services.google.gemini_api_key' => null]);
        $user = User::factory()->create(['role' => 'youth']);

        $this->actingAs($user)
            ->postJson(route('career-chat.store'), [
                'messages' => [['role' => 'user', 'text' => 'Help me explore careers.']],
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'AI chat is not configured yet. Add GOOGLE_AI_API_KEY to the server environment.');
    }

    public function test_youth_dashboard_places_chat_beside_actions_and_scans_one_document_at_a_time(): void
    {
        config(['services.google.gemini_api_key' => 'test-key']);
        $user = User::factory()->create(['role' => 'youth', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/youth')
            ->assertOk()
            ->assertSee('How can I help you?')
            ->assertSee('gemini-3.7-flash')
            ->assertSee('one document at a time')
            ->assertDontSee(' multiple', false);
    }

    public function test_admin_dashboard_displays_the_career_chat(): void
    {
        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('How can I help you?');
    }

    public function test_trainer_dashboard_displays_the_career_chat(): void
    {
        $user = User::factory()->create(['role' => 'trainer', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/trainer')
            ->assertOk()
            ->assertSee('How can I help you?');
    }
}
