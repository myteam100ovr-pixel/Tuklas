<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreDocumentScanRequest;
use App\Models\DocumentScan;
use App\Models\TrainingProgram;
use App\Services\GeminiDocumentScanner;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(StoreDocumentScanRequest $request, GeminiDocumentScanner $scanner): JsonResponse
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

        $scan = $user->documentScans()->create([
            'doc_type' => 'document',
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'stored_path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'processing',
            'progress' => 0,
        ]);

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

            $analysis = $scanner->analyze($file, $publishedPrograms);

            if ($user->hasRole(Role::Youth)) {
                $profile = $user->youthProfile()->firstOrNew();
                $profile->skills = $this->mergeProfileValues($profile->skills ?? [], $analysis['skills'] ?? []);
                $profile->credentials = $this->mergeProfileValues($profile->credentials ?? [], $analysis['credentials'] ?? []);

                if (! $profile->exists && ($profile->skills !== [] || $profile->credentials !== [])) {
                    $profile->save();
                } elseif ($profile->exists && ($analysis['skills'] !== [] || $analysis['credentials'] !== [])) {
                    $profile->save();
                }
            }

            $scan->update([
                'status' => 'done',
                'progress' => 100,
                'result' => $analysis,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $failureMessage = $this->failureMessage($exception);

            $scan->update([
                'status' => 'failed',
                'error' => $failureMessage,
                'processed_at' => now(),
            ]);

            return response()->json([
                'message' => $failureMessage,
                'document' => $this->presentDocument($scan),
            ], 502);
        } finally {
            Storage::disk('local')->delete($path);
            $scan->update(['stored_path' => null]);
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
        $previous = $exception->getPrevious();

        if ($previous instanceof RequestException
            && in_array($previous->response->status(), [502, 503, 504], true)) {
            return 'Gemini is temporarily unavailable. Please try again shortly.';
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
