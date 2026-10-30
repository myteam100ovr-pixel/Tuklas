<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use App\Services\GeminiDocumentScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\TestWith;

class DocumentScanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_scan_persists_the_detected_document_type_and_returns_insights(): void
    {
        $youth = $this->createYouth();
        Storage::fake('local');

        $this->mock(GeminiDocumentScanner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('analyze')->once()->andReturn([
                'documentType' => 'resume',
                'summary' => 'The resume shows entry-level web development experience.',
                'skills' => ['HTML'],
                'credentials' => [],
                'job_roles' => ['Junior Web Developer'],
                'tesda_training' => [],
                'next_steps' => ['Build a portfolio'],
                'skillsDetected' => ['HTML'],
                'careerMatches' => [],
                'jobRecommendations' => [['title' => 'Junior Web Developer']],
                'skillGaps' => ['Communication'],
                'tesdaRecommendations' => [],
                'learningRecommendations' => [['title' => 'Free web course']],
                'nextActions' => ['Build a portfolio'],
            ]);
        });

        $this->actingAs($youth, 'sanctum')
            ->post(route('mobile.document-scans.store'), [
                'document_type' => 'certificate',
                'file' => UploadedFile::fake()->createWithContent(
                    'certificate.pdf',
                    "%PDF-1.4\nSample certificate\n%%EOF",
                ),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('document.document_type', 'resume')
            ->assertJsonPath('document.analysis.jobRecommendations.0.title', 'Junior Web Developer')
            ->assertJsonPath('document.analysis.learningRecommendations.0.title', 'Free web course');

        $scan = DocumentScan::query()->sole();
        $this->assertSame('resume', $scan->doc_type);
        $this->assertSame('done', $scan->status);
        $this->assertNull($scan->stored_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(['HTML'], $youth->fresh()->youthProfile->skills);
    }

    #[TestWith(['study_material', 'document-scans.store'])]
    #[TestWith(['other', 'mobile.document-scans.store'])]
    #[TestWith(['unknown', 'document-scans.store'])]
    #[TestWith([null, 'document-scans.store'])]
    public function test_unaccepted_content_returns_422_without_saving_or_updating_profile(?string $type, string $route): void
    {
        $youth = $this->createYouth();
        $youth->youthProfile()->create(['skills' => ['Sewing'], 'credentials' => ['Existing credential']]);
        Storage::fake('local');
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.7-flash');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'documentType' => $type,
                'summary' => 'Unrelated content',
                'skillsDetected' => ['Must not be saved'],
                'credentials' => ['Must not be saved'],
            ])]]]]],
        ])]);

        $this->actingAs($youth, 'sanctum')->postJson(route($route), [
            'document_type' => 'resume',
            'file' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nUnrelated\n%%EOF"),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('document_scans', 0);
        $this->assertSame(['Sewing'], $youth->fresh()->youthProfile->skills);
        $this->assertSame(['Existing credential'], $youth->fresh()->youthProfile->credentials);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertSentCount(1);
    }

    public function test_certificate_scan_saves_tesda_recommendations_from_gemini(): void
    {
        $youth = $this->createYouth();
        $youth->youthProfile()->create(['assessment_preferences' => ['career_goal' => 'Open a neighborhood bakery', 'schedule' => 'Weekends']]);
        Storage::fake('local');
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.7-flash');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'documentType' => 'certificate',
                'summary' => 'Completed food preparation training.',
                'skillsDetected' => ['Food preparation'],
                'credentials' => ['Food preparation course completion'],
                'tesdaRecommendations' => ['Cookery (NC II) — Explore practical cooking training based on the food preparation certificate.'],
            ])]]]]],
        ])]);

        $response = $this->actingAs($youth)->postJson(route('document-scans.store'), [
            'goal' => 'Help me pursue a culinary career.',
            'file' => UploadedFile::fake()->createWithContent('training.pdf', "%PDF-1.4\nCertificate\n%%EOF"),
        ])->assertCreated()->assertJsonPath('document.document_type', 'certification');

        $scan = DocumentScan::query()->sole();
        $this->assertSame($response->json('document.analysis.tesdaRecommendations'), $scan->result['tesda_training']);
        $this->assertSame(['Food preparation'], $youth->fresh()->youthProfile->skills);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_contains(data_get($request->data(), 'contents.0.parts.0.text', ''), 'Help me pursue a culinary career.')
            && str_contains($request->body(), 'Open a neighborhood bakery')
            && str_contains($request->body(), 'Weekends')
            && str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), 'Cookery NC II')
            && ! str_contains(data_get($request->data(), 'systemInstruction.parts.0.text', ''), '2D Animation NC III (Superseded)'));
    }

    public function test_scan_calculates_career_skill_coverage_from_documented_role_skills(): void
    {
        $youth = $this->createYouth();
        Storage::fake('local');
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.7-flash');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'documentType' => 'resume',
                'summary' => 'HTML and CSS projects are documented.',
                'skillsDetected' => ['HTML', 'CSS'],
                'careerMatches' => [[
                    'name' => 'Web developer', 'match' => 'Some relevant projects',
                    'roleSkills' => ['HTML', 'CSS', 'JavaScript', 'Git'],
                    'evidencedSkills' => ['HTML', 'CSS', 'Unlisted skill'],
                ]],
                'jobRecommendations' => [[
                    'title' => 'Web developer', 'reason' => 'Projects align with web work.',
                    'roleSkills' => ['HTML', 'CSS', 'JavaScript', 'Git'],
                    'evidencedSkills' => ['HTML', 'CSS'],
                ]],
                'tesdaRecommendations' => ['Web Development NC III — Explore a suitable program.'],
            ])]]]]],
        ])]);

        $this->actingAs($youth)->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nResume\n%%EOF"),
        ])->assertCreated()
            ->assertJsonPath('document.analysis.careerMatches.0.skillCoverage.percent', 50)
            ->assertJsonPath('document.analysis.jobRecommendations.0.skillCoverage.matched', 2)
            ->assertJsonPath('document.analysis.jobRecommendations.0.skillsToDevelop.0', 'JavaScript');

        $this->assertSame(50, DocumentScan::query()->sole()->result['careerMatches'][0]['skillCoverage']['percent']);
        $this->get(route('youth.dashboard'))->assertSee('50% of listed role skills documented');
        $this->get(route('peso.index'))->assertSee('2 of 4 listed role skills documented in this scan');
        $this->getJson(route('mobile.peso.index'))->assertJsonPath('matches.0.job_recommendations.0.skillCoverage.percent', 50);
    }

    public function test_scan_fills_missing_tesda_recommendations_with_a_catalog_checked_ai_match(): void
    {
        $youth = $this->createYouth();
        Storage::fake('local');
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.7-flash');
        Http::preventStrayRequests();
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['candidates' => [['content' => ['parts' => [['text' => json_encode([
                'documentType' => 'resume',
                'summary' => 'Entry-level web experience.',
                'skillsDetected' => ['HTML', 'CSS'],
                'careerMatches' => [['name' => 'Web developer', 'match' => 'Experience with websites']],
                'tesdaRecommendations' => [],
            ])]]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => json_encode([
                'recommendations' => [
                    ['title' => 'Web Development NC III', 'reason' => 'HTML and CSS projects support this path; ask a registered provider about training.'],
                    ['title' => 'Invented AI NC V', 'reason' => 'Not in the official catalog.'],
                ],
            ])]]]]]]);

        $this->actingAs($youth)->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nResume\n%%EOF"),
        ])->assertCreated()
            ->assertJsonPath('document.analysis.tesdaRecommendations.0', 'Web Development NC III — HTML and CSS projects support this path; ask a registered provider about training.')
            ->assertJsonCount(1, 'document.analysis.tesdaRecommendations');

        $this->assertSame(DocumentScan::query()->sole()->result['tesdaRecommendations'], DocumentScan::query()->sole()->result['tesda_training']);
        Http::assertSentCount(2);
    }

    public function test_provider_failure_does_not_save_a_scan_or_leave_a_file(): void
    {
        $youth = $this->createYouth();
        Storage::fake('local');
        config()->set('services.google.gemini_api_key', 'test-key');
        config()->set('services.google.gemini_model', 'gemini-3.7-flash');
        Http::preventStrayRequests();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Quota exhausted']], 429)]);

        $this->actingAs($youth)->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nResume\n%%EOF"),
        ])->assertStatus(429)->assertJsonPath('provider_status', 429);

        $this->assertDatabaseCount('document_scans', 0);
        $this->assertDatabaseCount('youth_profiles', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertSentCount(1);
    }

    public function test_mobile_scan_rejects_unknown_document_types(): void
    {
        $youth = $this->createYouth();

        $this->actingAs($youth, 'sanctum')
            ->postJson(route('mobile.document-scans.store'), [
                'document_type' => 'passport',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_type');
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_scanner_retries_temporary_provider_errors_without_losing_status(bool $recovers): void
    {
        Sleep::fake();
        Storage::fake('local');
        config()->set('services.google.gemini_api_key', 'test-key');
        Http::preventStrayRequests();
        $sequence = Http::sequence()->push(['error' => ['message' => 'Busy']], 503);
        if ($recovers) {
            $sequence->push(['candidates' => [['content' => ['parts' => [['text' => json_encode(['documentType' => 'resume', 'summary' => 'Resume reviewed.'])]]]]]]);
        } else {
            $sequence->pushStatus(503)->pushStatus(503);
        }
        Http::fake(['generativelanguage.googleapis.com/*' => $sequence]);

        $response = $this->actingAs($this->createYouth())->postJson(route('document-scans.store'), [
            'file' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nResume\n%%EOF"),
        ]);

        if ($recovers) {
            $response->assertCreated();
        } else {
            $response->assertStatus(503)->assertJsonPath('provider_status', 503)
                ->assertJsonPath('message', 'Gemini is temporarily busy. Please try the scan again shortly.');
        }
        $this->assertDatabaseCount('document_scans', $recovers ? 1 : 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertSentCount($recovers ? 2 : 3);
    }

    private function createYouth(): User
    {
        $user = User::factory()->create();
        $user->role = Role::Youth;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
