<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\SendCareerChatRequest;
use App\Models\User;
use App\Services\AiContextService;
use App\Services\DocumentScanHistory;
use App\Services\GeminiCareerAssistant;
use App\Services\StarterCareerRecommendations;
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

    public function __invoke(
        SendCareerChatRequest $request,
        GeminiCareerAssistant $assistant,
        DocumentScanHistory $scanHistory,
        StarterCareerRecommendations $recommendations,
        ActivityNotifier $activity,
        AiContextService $aiContext,
    ): JsonResponse {
        if (! $assistant->isConfigured()) {
            return response()->json([
                'message' => 'TuklasBot is temporarily unavailable. Please tell a Tuklas administrator.',
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
            $systemContext = $aiContext->buildSystemContext($user, $message['text']);
            if (is_array($systemContext['user_profile'] ?? null)) {
                unset($systemContext['user_profile']['display_name']);
            }
            $result = $assistant->reply(
                [...$history, $message],
                $this->careerContext($user, $scanHistory, $recommendations),
                $systemContext,
            );
            $resolvedLinks = $aiContext->resolveChatLinks($result['reply'], $systemContext);
            $result['reply'] = $resolvedLinks['reply'];
            $result['recommended_links'] = $resolvedLinks['recommended_links'];
            $savedSources = [
                ...$result['sources'],
                ...array_map(fn (array $link): array => [
                    'type' => 'internal',
                    'title' => $link['label'],
                    'url' => $link['web_url'],
                    'mobile_route' => $link['mobile_route'],
                    'link_id' => $link['id'],
                ], $result['recommended_links']),
            ];

            DB::transaction(function () use ($user, $message, $result, $savedSources): void {
                $user->careerChatMessages()->create($message);
                $user->careerChatMessages()->create([
                    'role' => 'model',
                    'text' => $result['reply'],
                    'sources' => $savedSources,
                ]);
            });

            $activity->record($user, 'used TuklasBot.');

            return response()->json([
                'reply' => $result['reply'],
                'sources' => $result['sources'],
                'recommended_links' => $result['recommended_links'],
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
                    429 => 'TuklasBot has reached its current Google AI usage limit. Please try again later. If this continues, tell a Tuklas administrator.',
                    500, 502, 503, 504 => 'Gemini is temporarily busy. Please try again shortly.',
                    401, 403 => 'TuklasBot’s AI connection needs an administrator to check the server setup.',
                    404 => 'TuklasBot’s AI service is unavailable with the current setup. Please tell a Tuklas administrator.',
                    default => 'Gemini could not reply right now. Please try again.',
                },
                'provider_status' => $providerStatus > 0 ? $providerStatus : null,
            ], $responseStatus);
        }
    }

    /** @return array<string, mixed> */
    private function careerContext(?User $user, DocumentScanHistory $scanHistory, StarterCareerRecommendations $recommendations): array
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
            'published_initial_recommendations' => $recommendations->for($user),
            'recent_scan_results' => $scanResults,
        ], fn (mixed $value): bool => is_array($value) && $value !== []);
    }
}
