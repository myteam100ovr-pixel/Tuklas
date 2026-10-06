<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NavComposer
{
    private const TABS = [
        'super_admin' => [
            ['Overview', 'admin.dashboard', 'i-list'],
            ['People', 'admin.users.index', 'i-users'],
            ['PESO', 'peso.index', 'i-briefcase'],
            ['AI Scanner', 'scanner.index', 'i-scan'],
            ['TESDA', 'tesda.index', 'i-cap'],
        ],
        'trainer' => [
            ['Overview', 'trainer.dashboard', 'i-list'],
            ['PESO', 'peso.index', 'i-briefcase'],
            ['AI Scanner', 'scanner.index', 'i-scan'],
            ['TESDA', 'tesda.index', 'i-cap'],
        ],
        'youth' => [
            ['Overview', 'youth.dashboard', 'i-list'],
            ['PESO', 'peso.index', 'i-briefcase'],
            ['AI Scanner', 'scanner.index', 'i-scan'],
            ['TESDA', 'tesda.index', 'i-cap'],
        ],
    ];

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $tabs = collect(self::TABS[$user->role->value] ?? [])
            ->push(['Profile', 'profile.show', 'i-user'])
            ->filter(fn ($tab) => Route::has($tab[1]))
            ->map(function (array $tab): array {
                $pattern = Str::endsWith($tab[1], '.dashboard') ? $tab[1] : Str::beforeLast($tab[1], '.').'.*';

                return [
                    'label' => $tab[0],
                    'href' => route($tab[1]),
                    'icon' => $tab[2],
                    'active' => request()->routeIs($pattern),
                ];
            })
            ->values()->all();

        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

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
