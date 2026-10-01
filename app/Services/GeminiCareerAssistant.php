<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiCareerAssistant
{
    public function isConfigured(): bool
    {
        return filled(config('services.google.gemini_api_key'));
    }

    /** @param array<int, array{role: string, text: string}> $messages */
    public function reply(array $messages): string
    {
        $apiKey = config('services.google.gemini_api_key');
        $model = config('services.google.gemini_model');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Gemini is not configured.');
        }

        if (! is_string($model) || $model === '') {
            throw new RuntimeException('Gemini model is not configured.');
        }

        $contents = array_map(fn (array $message): array => [
            'role' => $message['role'],
            'parts' => [['text' => $message['text']]],
        ], $messages);

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
                ->connectTimeout(10)
                ->timeout(45)
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent', [
                    'system_instruction' => [
                        'parts' => [[
                            'text' => 'You are Tuklas, a friendly career and skills-training guide for youth in Bugallon, Pangasinan. Help users explore interests and practical next steps. Do not invent current TESDA schedules, local vacancies, or other facts you cannot verify. Never guarantee employment, admission, or eligibility. Do not ask for sensitive personal identifiers. Reply concisely in the user\'s language when possible.',
                        ]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.5,
                        'maxOutputTokens' => 700,
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Gemini could not reply.', previous: $exception);
        }

        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        $reply = collect($parts)
            ->pluck('text')
            ->filter(fn (mixed $text): bool => is_string($text) && trim($text) !== '')
            ->implode("\n");

        if ($reply === '') {
            throw new RuntimeException('Gemini returned no chat reply.');
        }

        return trim($reply);
    }
}
