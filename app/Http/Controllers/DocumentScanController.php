<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreDocumentScanRequest;
use App\Models\DocumentScan;
use App\Models\TrainingProgram;
use App\Services\GeminiDocumentScanner;
use App\Services\TesdaQualificationCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DocumentScanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()
            ->documentScans()
            ->latest()
            ->limit(8)
            ->get(['id', 'doc_type', 'original_name', 'status', 'result', 'error', 'created_at'])
            ->map(fn (DocumentScan $scan): array => $this->presentDocument($scan));

        return response()->json(['documents' => $documents]);
    }

    public function store(StoreDocumentScanRequest $request, GeminiDocumentScanner $scanner, TesdaQualificationCatalog $catalog): JsonResponse
    {
        if (! $scanner->isConfigured()) {
            return response()->json([
                'message' => 'AI scanning is not configured yet. Add GOOGLE_AI_API_KEY to the server environment.',
            ], 503);
        }

        $validated = $request->validated();
        $file = $validated['file'];
        $user = $request->user();
        $path = $file->store('document-scans/'.$user->getKey(), 'local');

        if (! is_string($path)) {
            return response()->json(['message' => 'The document could not be saved. Please try again.'], 500);
        }

        try {
            $publishedPrograms = TrainingProgram::published()
                ->orderBy('title')
                ->limit(40)
                ->get(['title', 'nc_level', 'description'])
                ->map(fn (TrainingProgram $program): array => [
                    'title' => $program->title,
                    'nc_level' => $program->nc_level,
                    'description' => Str::limit($program->description ?? '', 180),
                ])->all();

            $programs = collect($catalog->forScanner())->concat($publishedPrograms)->unique('title')->values()->all();
            $analysis = $scanner->analyze($file, $programs, $validated['goal'] ?? '');

            if (! in_array($analysis['documentType'] ?? null, ['resume', 'certificate'], true)) {
                return response()->json([
                    'message' => 'This file is not a readable resume or certificate. Upload a resume or certificate to receive career and TESDA recommendations. Nothing was saved.',
                    'errors' => ['file' => ['Only readable resumes and certificates are accepted.']],
                ], 422);
            }

            $scan = DB::transaction(function () use ($user, $file, $analysis): DocumentScan {
                if ($user->hasRole(Role::Youth)
                    && in_array($analysis['documentType'] ?? 'other', ['resume', 'certificate'], true)) {
                    $profile = $user->youthProfile()->firstOrNew();
                    $profile->skills = $this->mergeProfileValues($profile->skills ?? [], $analysis['skills'] ?? []);
                    $profile->credentials = $this->mergeProfileValues($profile->credentials ?? [], $analysis['credentials'] ?? []);

                    if (! $profile->exists && ($profile->skills !== [] || $profile->credentials !== [])) {
                        $profile->save();
                    } elseif ($profile->exists && ($analysis['skills'] !== [] || $analysis['credentials'] !== [])) {
                        $profile->save();
                    }
                }

                return $user->documentScans()->create([
                    'doc_type' => $analysis['documentType'],
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'stored_path' => null,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'status' => 'done',
                    'progress' => 100,
                    'result' => $analysis,
                    'processed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);
            $failureMessage = $this->failureMessage($exception);

            return response()->json([
                'message' => $failureMessage,
            ], 502);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return response()->json([
            'document' => $this->presentDocument($scan),
        ], 201);
    }

    /** @return array<string, mixed> */
    private function presentDocument(DocumentScan $scan): array
    {
        return [
            'id' => $scan->id,
            'document_type' => $scan->doc_type === 'certificate' ? 'certification' : $scan->doc_type,
            'original_name' => $scan->original_name,
            'status' => $scan->status === 'done' ? 'completed' : $scan->status,
            'analysis' => $scan->result,
            'failure_message' => $scan->error,
            'created_at' => $scan->created_at,
        ];
    }

    private function failureMessage(Throwable $exception): string
    {
        if ($exception->getCode() === 429) {
            return 'Gemini quota is exhausted for this Google AI project. Check the project’s AI Studio rate limits and billing, then try again.';
        }

        $previous = $exception->getPrevious();

        if (in_array($exception->getCode(), [500, 502, 503, 504], true)
            || ($previous instanceof RequestException
                && in_array($previous->response->status(), [500, 502, 503, 504], true))) {
            return 'Gemini is temporarily busy. Please try the scan again shortly.';
        }

        if ($previous instanceof ConnectionException) {
            return 'Gemini did not respond in time. Please try the scan again shortly.';
        }

        return 'Gemini could not finish this scan. Please try again.';
    }

    /** @param array<int, string>|null $existing
     * @param  array<int, string>  $suggested
     * @return array<int, string>
     */
    private function mergeProfileValues(?array $existing, array $suggested): array
    {
        $merged = $existing ?? [];
        $knownValues = array_map(fn (string $value): string => mb_strtolower(trim($value)), $merged);

        foreach ($suggested as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $normalized = mb_strtolower(trim($value));

            if (! in_array($normalized, $knownValues, true)) {
                $merged[] = trim($value);
                $knownValues[] = $normalized;
            }
        }

        return array_slice($merged, 0, 100);
    }
}
