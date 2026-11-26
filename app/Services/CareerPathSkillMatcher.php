<?php

namespace App\Services;

use App\Models\CareerPath;
use App\Models\YouthProfile;

class CareerPathSkillMatcher
{
    /**
     * @return array{status: string, matched: int|null, total: int|null, percent: int|null, matched_skills: array<int, string>, unselected_skills: array<int, string>}
     */
    public function match(CareerPath $careerPath, ?YouthProfile $profile): array
    {
        $requiredSkills = collect($careerPath->required_skills ?? [])->filter(fn (mixed $skill): bool => is_string($skill) && trim($skill) !== '')->unique()->values();

        if ($requiredSkills->count() < 3) {
            return ['status' => 'requirements_missing', 'matched' => null, 'total' => null, 'percent' => null, 'matched_skills' => [], 'unselected_skills' => []];
        }

        $assessmentSkills = $profile?->assessmentSkills();
        if ($assessmentSkills === null) {
            return ['status' => 'assessment_update_required', 'matched' => null, 'total' => null, 'percent' => null, 'matched_skills' => [], 'unselected_skills' => $requiredSkills->all()];
        }

        $selected = collect($assessmentSkills)->map(fn (string $skill): string => mb_strtolower(trim($skill)))->all();
        $matchedSkills = $requiredSkills->filter(fn (string $skill): bool => in_array(mb_strtolower($skill), $selected, true))->values();
        $unselectedSkills = $requiredSkills->diff($matchedSkills)->values();

        return [
            'status' => 'ready',
            'matched' => $matchedSkills->count(),
            'total' => $requiredSkills->count(),
            'percent' => (int) round($matchedSkills->count() / $requiredSkills->count() * 100),
            'matched_skills' => $matchedSkills->all(),
            'unselected_skills' => $unselectedSkills->all(),
        ];
    }
}
