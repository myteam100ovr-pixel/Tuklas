<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendCareerChatRequest;
use App\Services\GeminiCareerAssistant;
use Illuminate\Http\JsonResponse;
use Throwable;

class CareerChatController
{
    public function __invoke(SendCareerChatRequest $request, GeminiCareerAssistant $assistant): JsonResponse
    {
        if (! $assistant->isConfigured()) {
            return response()->json([
                'message' => 'AI chat is not configured yet. Add GOOGLE_AI_API_KEY to the server environment.',
            ], 503);
        }

        try {
            return response()->json([
                'reply' => $assistant->reply($request->validated('messages')),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getCode() === 429
                    ? 'Gemini quota is exhausted for this Google AI project. Check the project’s AI Studio rate limits and billing, then try again.'
                    : 'Gemini could not reply right now. Please try again.',
            ], 502);
        }
    }
}
