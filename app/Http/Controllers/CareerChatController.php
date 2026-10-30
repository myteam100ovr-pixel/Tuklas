<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\SendCareerChatRequest;
use App\Models\User;
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

    public function __invoke(SendCareerChatRequest $request, GeminiCareerAssistant $assistant, ActivityNotifier $activity): JsonResponse
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
                $this->latestCareerContext($user),
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

    /** @return array<string, array<int, string>> */
    private function latestCareerContext(?User $user): array
    {
        if (! $user instanceof User || ! $user->hasRole(Role::Youth)) {
            return [];
        }

        $preferences = $user->youthProfile?->careerPreferences() ?? [];
        $analysis = $user->documentScans()
            ->where('status', 'done')
            ->latest('processed_at')
            ->first(['result'])
            ?->result;

        if (! is_array($analysis) || ! in_array($analysis['documentType'] ?? null, ['resume', 'certificate'], true)) {
            return $preferences;
        }

        $cleanStrings = fn (mixed $values): array => collect(is_array($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => mb_substr(trim($value), 0, 120))
            ->take(8)
            ->values()
            ->all();

        $careerMatches = collect(is_array($analysis['careerMatches'] ?? null) ? $analysis['careerMatches'] : [])
            ->map(fn (mixed $match): mixed => is_array($match) ? ($match['name'] ?? null) : $match);
        $jobRecommendations = collect(is_array($analysis['jobRecommendations'] ?? null) ? $analysis['jobRecommendations'] : [])
            ->map(fn (mixed $recommendation): mixed => is_array($recommendation) ? ($recommendation['title'] ?? null) : null);

        return array_filter([
            ...$preferences,
            'skills_detected' => $cleanStrings($analysis['skillsDetected'] ?? []),
            'credentials' => $cleanStrings($analysis['credentials'] ?? []),
            'career_matches' => $cleanStrings($careerMatches->all()),
            'job_recommendations' => $cleanStrings($jobRecommendations->all()),
            'skill_gaps' => $cleanStrings($analysis['skillGaps'] ?? []),
        ], fn (array $values): bool => $values !== []);
    }
}
