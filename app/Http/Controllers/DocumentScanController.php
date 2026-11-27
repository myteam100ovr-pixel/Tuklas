<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreDocumentScanRequest;
use App\Models\DocumentScan;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use App\Services\AiContextService;
use App\Services\GeminiDocumentScanner;
use App\Services\TesdaQualificationCatalog;
use App\Support\ActivityNotifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DocumentScanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin, Role::Youth), 403);
        $documents = $request->user()
            ->documentScans()
            ->latest()
            ->limit(8)
            ->get(['id', 'doc_type', 'original_name', 'status', 'result', 'error', 'created_at'])
            ->map(fn (DocumentScan $scan): array => $this->presentDocument($scan));

        return response()->json(['documents' => $documents]);
    }

    public function store(StoreDocumentScanRequest $request, GeminiDocumentScanner $scanner, TesdaQualificationCatalog $catalog, AiContextService $aiContext, ActivityNotifier $activity): JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin, Role::Youth), 403);
        if (! $scanner->isConfigured()) {
            return response()->json([
                'message' => 'Document scanning is temporarily unavailable. Please tell a Tuklas administrator.',
            ], 503);
        }

        $validated = $request->validated();
        $file = $validated['file'];
        $user = $request->user();
        $temporaryPath = $file->getRealPath();
        $fingerprint = is_string($temporaryPath) ? hash_file('sha256', $temporaryPath) : false;

        if (! is_string($fingerprint)) {
            return response()->json(['message' => 'The selected document could not be read. Please choose it again.'], 422);
        }

        $requestFingerprint = hash('sha256', $fingerprint.'|'.trim($validated['goal'] ?? ''));
        $deduplicationKey = 'document-scan:'.$user->getKey().':'.$requestFingerprint;
        $cachedScanId = Cache::get($deduplicationKey);

        if (is_numeric($cachedScanId)) {
            $existingScan = $user->documentScans()->find($cachedScanId);

            if ($existingScan) {
                return response()->json([
                    'document' => $this->presentDocument($existingScan),
                    'duplicate' => true,
                    'message' => 'This exact file was scanned just now. Showing the saved result instead of creating a duplicate.',
                ]);
            }

            Cache::forget($deduplicationKey);
        }

        if (! Cache::add($deduplicationKey, 'processing', now()->addMinutes(5))) {
            $cachedScanId = Cache::get($deduplicationKey);
            $existingScan = is_numeric($cachedScanId)
                ? $user->documentScans()->find($cachedScanId)
                : null;

            if ($existingScan) {
                return response()->json([
                    'document' => $this->presentDocument($existingScan),
                    'duplicate' => true,
                    'message' => 'This exact file was scanned just now. Showing the saved result instead of creating a duplicate.',
                ]);
            }

            return response()->json(['message' => 'This exact document is already being scanned. Wait for it to finish before submitting it again.'], 409);
        }

        $path = $file->store('document-scans/'.$user->getKey(), 'local');

        if (! is_string($path)) {
            Cache::forget($deduplicationKey);

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
            $preferences = $user->hasRole(Role::Youth) ? ($user->youthProfile?->careerPreferences() ?? []) : [];
            $systemContext = $aiContext->buildSystemContext($user, $validated['goal'] ?? null);
            $systemContext['target_requirements'] = filled($validated['goal'] ?? null) ? trim($validated['goal']) : null;
            $analysis = $scanner->analyze($file, $programs, $validated['goal'] ?? '', $preferences, $systemContext);
            $analysis['recommended_links'] = collect($systemContext['links'] ?? [])
                ->keyBy('id')
                ->only($analysis['recommendedLinkIds'] ?? [])
                ->values()
                ->all();
            unset($analysis['recommendedLinkIds']);

            $scan = DB::transaction(function () use ($user, $file, $analysis): DocumentScan {
                if ($user->hasRole(Role::Youth)
                    && in_array($analysis['documentType'] ?? 'other', ['resume', 'certificate'], true)) {
                    $profile = $user->youthProfile()->lockForUpdate()->firstOrNew();
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
            Cache::put($deduplicationKey, $scan->getKey(), now()->addMinutes(5));
        } catch (Throwable $exception) {
            if ($exception instanceof \InvalidArgumentException) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['file' => [$exception->getMessage()]],
                ], 422);
            }

            report($exception);
            $failureMessage = $this->failureMessage($exception);
            $providerStatus = (int) $exception->getCode();
            $responseStatus = match ($providerStatus) {
                429 => 429,
                500, 502, 503, 504 => 503,
                default => 502,
            };

            return response()->json([
                'message' => $failureMessage,
                'provider_status' => $providerStatus > 0 ? $providerStatus : null,
            ], $responseStatus);
        } finally {
            Storage::disk('local')->delete($path);

            if (Cache::get($deduplicationKey) === 'processing') {
                Cache::forget($deduplicationKey);
            }
        }

        $this->sendOutcomeNotice($user, 'completed', $scan->original_name);
        $activity->record($user, 'completed a document scan.');

        return response()->json([
            'document' => $this->presentDocument($scan),
        ], 201);
    }

    public function reportOutcome(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin, Role::Youth), 403);

        $validated = $request->validate([
            'event_id' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:canceled,error,denied'],
            'file_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $eventKey = 'document-scan-notice:'.$user->getKey().':'.hash('sha256', $validated['event_id']);

        if (Cache::add($eventKey, true, now()->addDay())) {
            $fileName = $validated['file_name'] ?: 'Selected document';
            $this->sendOutcomeNotice($user, $validated['status'], $fileName);
        }

        return response()->json(['message' => 'The scan update was recorded.']);
    }

    private function sendOutcomeNotice(User $user, string $status, string $fileName): void
    {
        [$title, $message] = match ($status) {
            'completed' => ['AI scan completed', $fileName.' was scanned. Open Document Scanner to review the result.'],
            'canceled' => ['AI scan canceled', 'Scanning '.$fileName.' was canceled. You can select it again when ready.'],
            'denied' => ['AI scan access denied', 'Tuklas could not use '.$fileName.'. Check file or camera permissions and format, then try again.'],
            default => ['AI scan failed', 'Tuklas could not finish scanning '.$fileName.'. Check your connection and try again.'],
        };

        try {
            $user->notify(new WorkspaceNotice(
                $title,
                $message,
                'document-scan',
                null,
                route('scanner.index'),
            ));
        } catch (Throwable $exception) {
            Log::warning('The document scan finished, but its user notification could not be saved.', [
                'user_id' => $user->getKey(),
                'status' => $status,
                'exception' => $exception,
            ]);
        }
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
            return 'Tuklas document scanning has reached its current Google AI usage limit. Please try again later. If this continues, tell a Tuklas administrator.';
        }

        if (in_array($exception->getCode(), [401, 403], true)) {
            return 'The AI connection needs an administrator to check the server setup. Your document was not scanned.';
        }

        if ($exception->getCode() === 404) {
            return 'The AI service is unavailable with the current setup. Please tell a Tuklas administrator. Your document was not scanned.';
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
