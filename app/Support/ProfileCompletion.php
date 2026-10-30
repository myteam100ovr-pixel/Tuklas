<?php

namespace App\Support;

use App\Models\User;

class ProfileCompletion
{
    /** @return array{percent: int, completed: int, total: int, items: list<array{label: string, pct: int, completed: int, total: int, detail: string}>} */
    public static function for(User $user): array
    {
        $profile = $user->youthProfile;
        $preferences = $profile?->assessment_preferences ?? [];
        $hasValues = fn (?array $values): bool => collect($values ?? [])
            ->contains(fn (mixed $value): bool => is_string($value) && trim($value) !== '');
        $groups = [
            'Personal details' => [filled($user->date_of_birth), filled($profile?->barangay), filled($profile?->contact_number)],
            'Schooling and work' => [filled($profile?->educational_attainment), filled($profile?->employment_status)],
            'Career direction' => [filled($profile?->livelihood_interests) || $hasValues($profile?->interests), filled($preferences['priority'] ?? null), filled($preferences['career_goal'] ?? null)],
            'Learning preferences' => [filled($preferences['learning_mode'] ?? null), filled($preferences['schedule'] ?? null), filled($preferences['weekly_hours'] ?? null)],
        ];
        if ($user->date_of_birth && $user->date_of_birth->age < 18) {
            $groups['Guardian details'] = [filled($profile?->guardian_name), filled($profile?->guardian_relationship), filled($profile?->guardian_contact)];
        }
        $items = [];
        $completed = 0;
        $total = 0;
        foreach ($groups as $label => $checks) {
            $count = count(array_filter($checks));
            $size = count($checks);
            $completed += $count;
            $total += $size;
            $items[] = ['label' => $label, 'pct' => (int) round($count / $size * 100), 'completed' => $count, 'total' => $size, 'detail' => $count.' of '.$size.' details added'];
        }

        return ['percent' => (int) round($completed / $total * 100), 'completed' => $completed, 'total' => $total, 'items' => $items];
    }
}
