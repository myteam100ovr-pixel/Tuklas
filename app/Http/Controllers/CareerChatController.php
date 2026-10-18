<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\SendCareerChatRequest;
use App\Models\User;
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
            $result = $assistant->reply(
                $request->validated('messages'),
                $this->latestCareerContext($request->user()),
            );

            return response()->json([
                'reply' => $result['reply'],
                'sources' => $result['sources'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => match ($exception->getCode()) {
                    429 => 'Gemini quota is exhausted for this Google AI project. Check the project’s AI Studio rate limits and billing, then try again.',
                    500, 502, 503, 504 => 'Gemini is temporarily busy. Please try again shortly.',
                    401, 403 => 'Gemini authentication failed. Ask the administrator to check the server API key.',
                    404 => 'The configured Gemini model is unavailable. Ask the administrator to check the model configuration.',
                    default => 'Gemini could not reply right now. Please try again.',
                },
            ], 502);
        }
    }

    /** @return array<string, array<int, string>> */
    private function latestCareerContext(?User $user): array
    {
        if (! $user instanceof User || ! $user->hasRole(Role::Youth)) {
            return [];
        }

        $analysis = $user->documentScans()
            ->where('status', 'done')
            ->latest('processed_at')
            ->first(['result'])
            ?->result;

        if (! is_array($analysis) || ! in_array($analysis['documentType'] ?? null, ['resume', 'certificate'], true)) {
            return [];
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
            'skills_detected' => $cleanStrings($analysis['skillsDetected'] ?? []),
            'credentials' => $cleanStrings($analysis['credentials'] ?? []),
            'career_matches' => $cleanStrings($careerMatches->all()),
            'job_recommendations' => $cleanStrings($jobRecommendations->all()),
            'skill_gaps' => $cleanStrings($analysis['skillGaps'] ?? []),
        ], fn (array $values): bool => $values !== []);
    }
}
