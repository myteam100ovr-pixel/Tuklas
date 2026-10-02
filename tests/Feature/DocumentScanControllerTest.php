<?php

namespace Tests\Feature;

use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentScanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_retries_a_temporary_gemini_unavailable_response_and_completes_the_scan(): void
    {
        config([
            'services.google.gemini_api_key' => 'test-key',
            'services.google.gemini_model' => 'gemini-3.7-flash',
        ]);
        Storage::fake('local');
        Http::preventStrayRequests();
        $analysisText = implode("\n", [
            'Summary',
            'A concise scan summary.',
            'Skills found',
            '- PHP',
            '- Communication',
            'Qualifications and certificates',
            '- National Certificate II in Cookery',
            'Suggested job roles',
            '- Junior developer',
            'TESDA training to consider',
            '- Bread and Pastry Production NC II',
            'Next steps',
            '- Review your resume.',
        ]);
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503)
            ->push([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => $analysisText]],
                    ],
                ]],
            ], 200);
        $user = User::factory()->create(['role' => 'youth', 'is_active' => true]);
        $user->youthProfile()->create([
            'skills' => ['Manually added skill'],
            'credentials' => ['Existing certificate'],
        ]);

        $response = $this->actingAs($user)->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->image('sample-resume.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('document.status', 'completed')
            ->assertJsonPath('document.analysis.summary', $analysisText)
            ->assertJsonPath('document.analysis.skills', ['PHP', 'Communication'])
            ->assertJsonPath('document.analysis.credentials', ['National Certificate II in Cookery'])
            ->assertJsonPath('document.analysis.job_roles', ['Junior developer'])
            ->assertJsonPath('document.analysis.tesda_training', ['Bread and Pastry Production NC II']);
        Http::assertSentCount(2);

        $scan = DocumentScan::query()->where('user_id', $user->id)->sole();
        $this->assertSame('done', $scan->status);
        $this->assertSame($response->json('document.analysis.summary'), $scan->result['summary']);
        $this->assertNull($scan->stored_path);
        $this->assertSame(['Manually added skill', 'PHP', 'Communication'], $user->fresh()->youthProfile->skills);
        $this->assertSame(['Existing certificate', 'National Certificate II in Cookery'], $user->fresh()->youthProfile->credentials);
    }

    public function test_returns_a_temporary_unavailable_message_when_both_gemini_attempts_return_503(): void
    {
        config([
            'services.google.gemini_api_key' => 'test-key',
            'services.google.gemini_model' => 'gemini-3.7-flash',
        ]);
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503)
            ->push(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503);
        $user = User::factory()->create(['role' => 'youth', 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->image('sample-resume.jpg'),
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Gemini is temporarily unavailable. Please try again shortly.')
            ->assertJsonPath('document.failure_message', 'Gemini is temporarily unavailable. Please try again shortly.');
        Http::assertSentCount(2);

        $scan = DocumentScan::query()->where('user_id', $user->id)->sole();
        $this->assertSame('failed', $scan->status);
        $this->assertSame('Gemini is temporarily unavailable. Please try again shortly.', $scan->error);
        $this->assertNull($scan->stored_path);
    }

    public function test_reports_a_timeout_without_repeating_the_document_generation_request(): void
    {
        config([
            'services.google.gemini_api_key' => 'test-key',
            'services.google.gemini_model' => 'gemini-3.7-flash',
        ]);
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::failedConnection(),
        ]);
        $user = User::factory()->create(['role' => 'youth', 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->image('sample-resume.jpg'),
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Gemini did not respond in time. Please try the scan again shortly.');
        Http::assertSentCount(1);

        $scan = DocumentScan::query()->where('user_id', $user->id)->sole();
        $this->assertSame('failed', $scan->status);
        $this->assertNull($scan->stored_path);
    }
}
