<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\SendCareerChatRequest;
use App\Models\User;
use App\Services\DocumentScanHistory;
use App\Services\GeminiCareerAssistant;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CareerChatController
{
    public function index(Request $request): JsonResponse
    {
        $messages = $request->user()->careerChatMessages()
            ->latest('id')
            ->limit(100)
            ->get(['id', 'role', 'text', 'sources', 'created_at'])
            ->reverse()
            ->values();

        return response()->json(['messages' => $messages]);
    }

    public function __invoke(SendCareerChatRequest $request, GeminiCareerAssistant $assistant, DocumentScanHistory $scanHistory, ActivityNotifier $activity): JsonResponse
    {
        if (! $assistant->isConfigured()) {
            return response()->json([
                'message' => 'AI chat is not configured yet. Add GOOGLE_AI_API_KEY to the server environment.',
            ], 503);
        }

        try {
            $user = $request->user();
            $message = collect($request->validated('messages'))->last();
            $history = $user->careerChatMessages()
                ->latest('id')
                ->limit(9)
                ->get(['role', 'text'])
                ->reverse()
                ->map(fn ($item): array => ['role' => $item->role, 'text' => $item->text])
                ->values()
                ->all();
            $result = $assistant->reply(
                [...$history, $message],
                $this->careerContext($user, $scanHistory),
            );

            DB::transaction(function () use ($user, $message, $result): void {
                $user->careerChatMessages()->create($message);
                $user->careerChatMessages()->create([
                    'role' => 'model',
                    'text' => $result['reply'],
                    'sources' => $result['sources'],
                ]);
            });

            $activity->record($user, 'used TuklasBot.');

            return response()->json([
                'reply' => $result['reply'],
                'sources' => $result['sources'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $providerStatus = (int) $exception->getCode();
            $responseStatus = match ($providerStatus) {
                429 => 429,
                500, 502, 503, 504 => 503,
                default => 502,
            };

            return response()->json([
                'message' => match ($providerStatus) {
                    429 => 'Gemini quota is exhausted for this Google AI project. Check the project’s AI Studio rate limits and billing, then try again.',
                    500, 502, 503, 504 => 'Gemini is temporarily busy. Please try again shortly.',
                    401, 403 => 'Gemini authentication failed. Ask the administrator to check the server API key.',
                    404 => 'The configured Gemini model is unavailable. Ask the administrator to check the model configuration.',
                    default => 'Gemini could not reply right now. Please try again.',
                },
                'provider_status' => $providerStatus > 0 ? $providerStatus : null,
            ], $responseStatus);
        }
    }

    /** @return array<string, mixed> */
    private function careerContext(?User $user, DocumentScanHistory $scanHistory): array
    {
        if (! $user instanceof User || ! $user->hasRole(Role::Youth)) {
            return [];
        }

        $preferences = $user->youthProfile?->careerPreferences() ?? [];
        $scanResults = collect($scanHistory->recommendationsFor($user, 5))
            ->map(fn (array $scan): array => collect($scan)->except(['id', 'original_name'])->all())
            ->values()->all();

        return array_filter([
            ...$preferences,
            'user_profile_skills' => $user->youthProfile?->skills ?? [],
            'user_profile_credentials' => $user->youthProfile?->credentials ?? [],
            'recent_scan_results' => $scanResults,
        ], fn (mixed $value): bool => is_array($value) && $value !== []);
    }
}
