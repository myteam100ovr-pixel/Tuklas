<?php

namespace Tests\Feature;

use App\Services\GeminiCareerAssistant;
use App\Services\GeminiDocumentScanner;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GeminiModelFallbackTest extends TestCase
{
    #[TestWith(['connection', 2])]
    #[TestWith(['overloaded', 3])]
    public function test_career_assistant_uses_fallback_model_after_primary_failure(string $failure, int $requestCount): void
    {
        Sleep::fake();
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.5-flash-lite');
        config()->set('services.google.gemini_fallback_model', 'gemini-3.6-flash');
        config()->set('services.google.gemini_chat_web_search', false);
        Http::preventStrayRequests();

        $primaryResponses = Http::sequence();
        if ($failure === 'connection') {
            $primaryResponses->pushFailedConnection();
        } else {
            $primaryResponses->pushStatus(503)->pushStatus(503);
        }
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent' => $primaryResponses,
            'generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'A helpful answer.']]]]],
            ]),
        ]);

        $result = (new GeminiCareerAssistant)->reply([
            ['role' => 'user', 'text' => 'Hello'],
        ]);

        $this->assertSame('A helpful answer.', $result['reply']);
        Http::assertSentCount($requestCount);
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-3.5-flash-lite:generateContent',
        ));
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-3.6-flash:generateContent',
        ));
    }

    #[TestWith(['connection', 2])]
    #[TestWith(['overloaded', 4])]
    public function test_document_scanner_uses_fallback_model_after_primary_failure(string $failure, int $requestCount): void
    {
        Sleep::fake();
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.5-flash-lite');
        config()->set('services.google.gemini_fallback_model', 'gemini-3.6-flash');
        Http::preventStrayRequests();

        $primaryResponses = Http::sequence();
        if ($failure === 'connection') {
            $primaryResponses->pushFailedConnection();
        } else {
            $primaryResponses->pushStatus(503)->pushStatus(503)->pushStatus(503);
        }
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent' => $primaryResponses,
            'generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'documentType' => 'resume',
                        'summary' => 'A supported career report.',
                    ])]]],
                ]],
            ]),
        ]);

        $result = (new GeminiDocumentScanner)->analyze(
            UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nResume\n%%EOF"),
        );

        $this->assertSame('resume', $result['documentType']);
        $this->assertSame('A supported career report.', $result['summary']);
        Http::assertSentCount($requestCount);
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-3.5-flash-lite:generateContent',
        ));
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/models/gemini-3.6-flash:generateContent',
        ));
    }
}
