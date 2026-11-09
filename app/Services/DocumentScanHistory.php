<?php

namespace App\Services;

use App\Models\User;

class DocumentScanHistory
{
    /** @return array<int, array<string, mixed>> */
    public function recommendationsFor(User $user, ?int $limit = null): array
    {
        $scans = $user->documentScans()
            ->where('status', 'done')
            ->latest('processed_at');

        if ($limit !== null) {
            $scans->limit($limit);
        }

        return $scans
            ->get(['id', 'doc_type', 'original_name', 'result', 'processed_at'])
            ->map(function ($scan): array {
                $result = $scan->result ?? [];

                return [
                    'id' => $scan->getKey(),
                    'document_type' => $scan->doc_type,
                    'original_name' => $scan->original_name,
                    'processed_at' => $scan->processed_at?->toIso8601String(),
                    'skills' => $this->strings($result['skills'] ?? $result['skillsDetected'] ?? []),
                    'credentials' => $this->strings($result['credentials'] ?? []),
                    'career_matches' => $this->strings(collect($result['careerMatches'] ?? $result['job_roles'] ?? [])
                        ->map(fn (mixed $match): mixed => is_array($match) ? ($match['name'] ?? $match['title'] ?? null) : $match)
                        ->all()),
                    'tesda_recommendations' => $this->strings($result['tesdaRecommendations'] ?? $result['tesda_training'] ?? []),
                    'job_recommendations' => collect($result['jobRecommendations'] ?? [])
                        ->filter(fn (mixed $item): bool => is_array($item) && filled($item['title'] ?? null))
                        ->values()->all(),
                    'skill_gaps' => $this->strings($result['skillGaps'] ?? []),
                    'learning_recommendations' => collect($result['learningRecommendations'] ?? [])
                        ->filter(fn (mixed $item): bool => is_array($item) && filled($item['title'] ?? null))
                        ->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function strings(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();
    }
}
