<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use App\Services\GeminiDocumentScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

class DocumentScanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_scan_persists_the_selected_document_type_and_returns_insights(): void
    {
        $youth = $this->createYouth();
        Storage::fake('local');

        $this->mock(GeminiDocumentScanner::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('analyze')->once()->andReturn([
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
            ->assertJsonPath('document.document_type', 'certification')
            ->assertJsonPath('document.analysis.jobRecommendations.0.title', 'Junior Web Developer')
            ->assertJsonPath('document.analysis.learningRecommendations.0.title', 'Free web course');

        $scan = DocumentScan::query()->sole();
        $this->assertSame('certificate', $scan->doc_type);
        $this->assertSame('done', $scan->status);
        $this->assertNull($scan->stored_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
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
