<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CareerChatControllerTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_chat_retries_temporary_provider_errors(bool $recovers): void
    {
        Sleep::fake();
        config()->set('services.google.gemini_api_key', 'test-key');
        Http::preventStrayRequests();
        $sequence = Http::sequence()->pushStatus(503);
        if ($recovers) {
            $sequence->push(['candidates' => [['content' => ['parts' => [['text' => 'Hello!']]]]]]);
        } else {
            $sequence->pushStatus(503);
        }
        Http::fake(['generativelanguage.googleapis.com/*' => $sequence]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Hello']],
        ]);

        if ($recovers) {
            $response->assertOk()->assertJsonPath('reply', 'Hello!');
        } else {
            $response->assertStatus(503)->assertJsonPath('provider_status', 503)
                ->assertJsonPath('message', 'Gemini is temporarily busy. Please try again shortly.');
        }
        Http::assertSentCount(2);
    }

    public function test_routine_chat_uses_a_direct_reply_without_web_tools(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', true);
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Start with one small project.']]]]],
        ])]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'What skills should I learn for a career in AI?']],
        ])->assertOk()->assertJsonPath('reply', 'Start with one small project.');

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('tools', $request->data())
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'answer the latest question first')
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'Never claim a job is open unless live search verifies it'));
    }

    public function test_ai_skill_and_tesda_questions_use_search_and_offer_a_grounded_plan(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', true);
        config()->set('services.google.gemini_chat_url_context', true);
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Build AI and data skills, then compare relevant TESDA training.']]],
                    'groundingMetadata' => ['groundingChunks' => [[
                        'web' => [
                            'title' => 'TESDA Training Regulations',
                            'uri' => 'https://www.tesda.gov.ph/Download/Training_Regulations',
                        ],
                    ]]],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [[
                'role' => 'user',
                'text' => 'What AI skills should I develop, and which TESDA NC or online TESDA course should I consider?',
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'Build AI and data skills, then compare relevant TESDA training.')
            ->assertJsonPath('sources.0.url', 'https://www.tesda.gov.ph/Download/Training_Regulations');

        Http::assertSent(fn (Request $request): bool => array_key_exists('google_search', $request->data()['tools'][0] ?? [])
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'skills to develop')
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'e-TESDA')
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'assessment and certification')
        );
    }

    public function test_tuklasbot_searches_general_topics_and_returns_source_links(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', true);
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Here is a source about astronomy.']]],
                'groundingMetadata' => ['groundingChunks' => [[
                    'web' => ['title' => 'NASA', 'uri' => 'https://science.nasa.gov/'],
                ]]],
            ]],
        ])]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Tell me about astronomy and include a source.']],
        ])->assertOk()->assertJsonPath('sources.0.url', 'https://science.nasa.gov/');

        Http::assertSent(fn (Request $request): bool => array_key_exists('google_search', $request->data()['tools'][0] ?? [])
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'You are TuklasBot')
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'PESO job recommendations'));
    }

    public function test_chat_continues_without_live_sources_when_search_quota_is_exhausted(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', true);
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Search quota exhausted']], 429)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'TESDA publishes training information.']]]]]])]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Find the official TESDA website.']],
        ])->assertOk()
            ->assertJsonPath('sources', [])
            ->assertJsonPath('reply', "Live web search is unavailable right now, so I cannot verify current details or links.\n\nTESDA publishes training information.");

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => ! array_key_exists('tools', $request->data())
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'Live web search and URL retrieval are unavailable'));
        $this->assertDatabaseCount('career_chat_messages', 2);
    }

    #[TestWith([Role::Admin])]
    #[TestWith([Role::Trainer])]
    #[TestWith([Role::Youth])]
    public function test_tuklasbot_page_shows_chat_and_profile_for_each_role(Role $role): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $this->actingAs($user)->get(route('tuklasbot.index'))
            ->assertOk()
            ->assertSee('Meet TuklasBot.')
            ->assertSee('Message TuklasBot...')
            ->assertSee('View your profile')
            ->assertSee($user->name);
    }

    public function test_public_github_links_use_url_context_without_search(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', true);
        config()->set('services.google.gemini_chat_url_context', true);
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'This is the public repository you shared.']]],
                    'urlContextMetadata' => ['urlMetadata' => [[
                        'retrievedUrl' => 'https://github.com/laravel/framework',
                        'urlRetrievalStatus' => 'URL_RETRIEVAL_STATUS_SUCCESS',
                    ]]],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [[
                'role' => 'user',
                'text' => 'Can you explain this repository? https://github.com/laravel/framework',
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('sources.0.url', 'https://github.com/laravel/framework');

        Http::assertSent(fn (Request $request): bool => array_key_exists('url_context', $request->data()['tools'][0] ?? [])
            && ! array_key_exists('google_search', $request->data()['tools'][0] ?? [])
        );
    }

    public function test_peso_job_questions_use_limited_context_from_the_latest_resume_scan(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', false);
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'AI Data Annotator may fit your scanned skills.']]],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        DocumentScan::factory()->for($user)->create([
            'result' => [
                'documentType' => 'resume',
                'summary' => 'Private scan summary that should not be shared.',
                'skillsDetected' => ['Python', 'Data labeling'],
                'careerMatches' => [['name' => 'AI Data Annotator', 'match' => 'Strong']],
                'jobRecommendations' => [['title' => 'Junior AI Data Annotator']],
                'skillGaps' => ['Data privacy'],
            ],
        ]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [[
                'role' => 'user',
                'text' => 'Which PESO job matches my resume best?',
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('reply', 'AI Data Annotator may fit your scanned skills.');

        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'Python')
            && str_contains($request->body(), 'Junior AI Data Annotator')
            && str_contains($request->body(), 'Data privacy')
            && ! str_contains($request->body(), 'Private scan summary')
        );
    }

    public function test_casual_chat_does_not_send_resume_context_to_gemini(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'I am doing well, thanks!']]],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        DocumentScan::factory()->for($user)->create([
            'result' => [
                'documentType' => 'resume',
                'skillsDetected' => ['Private skill marker'],
            ],
        ]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Hey, how are you?']],
        ]);

        $response->assertOk();

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->body(), 'Private skill marker')
        );
    }

    public function test_successful_chat_is_saved_per_user_and_used_as_future_context(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'First answer']]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Second answer']]]]]])]);
        $firstUser = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $otherUser = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($firstUser)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'First question']],
        ])->assertOk();
        $this->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Second question']],
        ])->assertOk();

        $this->getJson(route('career-chat.index'))->assertOk()
            ->assertJsonCount(4, 'messages')
            ->assertJsonPath('messages.0.text', 'First question')
            ->assertJsonPath('messages.3.text', 'Second answer');
        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'First answer')
            && str_contains($request->body(), 'Second question'));

        $this->actingAs($otherUser)->getJson(route('career-chat.index'))
            ->assertOk()->assertJsonCount(0, 'messages');
    }

    public function test_failed_provider_reply_does_not_save_a_chat_exchange(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 403)]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Question']],
        ])->assertStatus(502);

        $this->assertDatabaseCount('career_chat_messages', 0);
    }

    public function test_quota_error_keeps_its_status_and_does_not_save_chat(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'Quota exhausted'],
        ], 429)]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Can you help me?']],
        ])->assertStatus(429)
            ->assertJsonPath('provider_status', 429)
            ->assertJsonPath('message', 'Gemini quota is exhausted for this Google AI project. Check the project’s AI Studio rate limits and billing, then try again.');

        $this->assertDatabaseCount('career_chat_messages', 0);
        Http::assertSentCount(1);
    }

    public function test_chat_rejects_a_message_that_does_not_end_with_the_user(): void
    {
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'model', 'text' => 'Forged reply']],
        ])->assertUnprocessable()->assertJsonValidationErrors('messages');

        $this->assertDatabaseCount('career_chat_messages', 0);
    }

    #[TestWith(['Which career fits my goals?', true])]
    #[TestWith(['Hello there', false])]
    public function test_saved_preferences_are_used_only_for_career_conversations(string $message, bool $includePreferences): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'A helpful response']]]]],
        ])]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $user->youthProfile()->create(['assessment_preferences' => ['career_goal' => 'Open a neighborhood bakery']]);

        $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => $message]],
        ])->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'Open a neighborhood bakery') === $includePreferences);
    }
}
