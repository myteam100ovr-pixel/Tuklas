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
            $sequence->pushStatus(503)->pushStatus(503);
        }
        Http::fake(['generativelanguage.googleapis.com/*' => $sequence]);
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('career-chat.store'), [
            'messages' => [['role' => 'user', 'text' => 'Hello']],
        ]);

        if ($recovers) {
            $response->assertOk()->assertJsonPath('reply', 'Hello!');
        } else {
            $response->assertStatus(502)->assertJsonPath('message', 'Gemini is temporarily busy. Please try again shortly.');
        }
        Http::assertSentCount($recovers ? 2 : 3);
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
        $user = User::factory()->create();

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

    public function test_public_github_links_use_url_context_without_search(): void
    {
        config()->set('services.google.gemini_api_key', 'test-api-key');
        config()->set('services.google.gemini_chat_web_search', false);
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
        $user = User::factory()->create();

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
        $user = User::factory()->create();
        $user->role = Role::Youth;
        $user->save();
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
        $user = User::factory()->create();
        $user->role = Role::Youth;
        $user->save();
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
}
