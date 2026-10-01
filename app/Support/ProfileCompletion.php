<?php

namespace App\Support;

use App\Models\User;

class ProfileCompletion
{
    /**
     * @return array{percent: int, items: list<array{label: string, pct: int}>}
     */
    public static function for(User $user): array
    {
        $profile = $user->youthProfile;

        $personal = collect([$user->date_of_birth, $profile?->barangay, $profile?->contact_number])
            ->filter()->count();

        $items = [
            ['label' => 'Personal details', 'pct' => (int) round($personal / 3 * 100)],
            ['label' => 'Schooling', 'pct' => filled($profile?->educational_attainment) ? 100 : 0],
            ['label' => 'Skills', 'pct' => count($profile?->skills ?? []) > 0 ? 100 : 0],
            ['label' => 'Credentials', 'pct' => count($profile?->credentials ?? []) > 0 ? 100 : 0],
            ['label' => 'Interests', 'pct' => (filled($profile?->livelihood_interests) || count($profile?->interests ?? []) > 0) ? 100 : 0],
        ];

        return ['percent' => (int) round(collect($items)->avg('pct')), 'items' => $items];
    }
}
