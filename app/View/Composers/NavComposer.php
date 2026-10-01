<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NavComposer
{
    private const TABS = [
        'super_admin' => [
            ['Overview', 'admin.dashboard'],
            ['Users', 'admin.users.index'],
            ['Careers', 'admin.careers.index'],
            ['Trainings', 'admin.trainings.index'],
        ],
        'trainer' => [
            ['Overview', 'trainer.dashboard'],
            ['Programs', 'trainer.programs.index'],
        ],
        'youth' => [
            ['Overview', 'youth.dashboard'],
            ['AI Scan', 'youth.scan.index'],
            ['Careers', 'youth.careers.index'],
            ['Trainings', 'youth.trainings.index'],
        ],
    ];

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $tabs = collect(self::TABS[$user->role->value] ?? [])
            ->push(['Profile', 'profile.show'])
            ->filter(fn (array $tab): bool => Route::has($tab[1]))
            ->map(function (array $tab): array {
                $pattern = Str::endsWith($tab[1], '.dashboard')
                    ? $tab[1]
                    : Str::beforeLast($tab[1], '.').'.*';

                return [
                    'label' => $tab[0],
                    'href' => route($tab[1]),
                    'active' => request()->routeIs($pattern),
                ];
            })
            ->values()
            ->all();

        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        $view->with([
            'navTabs' => $tabs,
            'navUser' => [
                'name' => $user->name,
                'email' => $user->email,
                'initials' => $initials,
                'photo' => $user->profile_photo_path ? $user->profile_photo_url : null,
                'role' => $user->role->label(),
            ],
        ]);
    }
}
