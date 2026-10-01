<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentScanRequest;
use App\Services\GeminiDocumentScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DocumentScanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()
            ->documentScans()
            ->latest()
            ->limit(8)
            ->get(['id', 'document_type', 'original_name', 'status', 'analysis', 'failure_message', 'created_at']);

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
            'document_type' => $validated['document_type'],
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'processing',
        ]);

        try {
            $analysis = $scanner->analyze($file, $scan->document_type);

            $scan->update([
                'status' => 'completed',
                'analysis' => $analysis,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $scan->update([
                'status' => 'failed',
                'failure_message' => 'Gemini could not finish this scan. Please try again.',
                'processed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Gemini could not finish this scan. Please try again.',
                'document' => $scan->only(['id', 'document_type', 'original_name', 'status', 'failure_message', 'created_at']),
            ], 502);
        }

        return response()->json([
            'document' => $scan->only(['id', 'document_type', 'original_name', 'status', 'analysis', 'created_at']),
        ], 201);
    }
}
