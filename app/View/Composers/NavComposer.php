<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NavComposer
{
    private const TABS = [
        'tesda_admin' => [
            ['Overview', 'trainer.dashboard', 'i-list'],
            ['Training centers', 'centers.index', 'i-cap'],
            ['Programs', 'trainer.programs.index', 'i-cap'],
            ['Assign trainers', 'center-trainers.index', 'i-users'],
            ['Training dates', 'sessions.index', 'i-cap'],
            ['Training requests', 'applications.index', 'i-list'],
            ['Assessment results', 'assessments.index', 'i-list'],
            ['Scholarships', 'scholarships.index', 'i-cap'],
            ['Messages', 'messages.index', 'i-chat'],
            ['Notifications', 'notifications.index', 'i-check'],
            ['Profile', 'profile.show', 'i-user'],
        ],
        'center_representative' => [
            ['Overview', 'trainer.dashboard', 'i-list'],
            ['My center', 'centers.index', 'i-cap'],
            ['Programs', 'trainer.programs.index', 'i-cap'],
            ['Assign trainers', 'center-trainers.index', 'i-users'],
            ['Training dates', 'sessions.index', 'i-cap'],
            ['Training requests', 'applications.index', 'i-users'],
            ['Assessment results', 'assessments.index', 'i-list'],
            ['Messages', 'messages.index', 'i-chat'],
            ['Notifications', 'notifications.index', 'i-check'],
            ['Profile', 'profile.show', 'i-user'],
        ],
    ];

    private const ROLE_GROUPS = [
        'tesda_admin' => [
            ['Overview', null, 'i-list', [
                ['Overview', 'trainer.dashboard', ['trainer.dashboard']],
            ]],
            ['Training', 'tesda_training', 'i-cap', [
                ['Centers', 'centers.index', ['centers.*']],
                ['Programs', 'trainer.programs.index', ['trainer.programs.*']],
                ['Assign trainers', 'center-trainers.index', ['center-trainers.*']],
                ['Training dates', 'sessions.index', ['sessions.*']],
                ['Training requests', 'applications.index', ['applications.*']],
                ['Assessment results', 'assessments.index', ['assessments.*']],
                ['Scholarships', 'scholarships.index', ['scholarships.*']],
            ]],
            ['Communication', 'tesda_communication', 'i-chat', [
                ['Messages', 'messages.index', ['messages.*']],
                ['Notifications', 'notifications.index', ['notifications.*']],
            ]],
            ['Account', 'tesda_account', 'i-user', [
                ['Profile', 'profile.show', ['profile.*']],
            ]],
        ],
        'trainer' => [
            ['Overview', null, 'i-list', [
                ['Overview', 'trainer.dashboard', ['trainer.dashboard']],
            ]],
            ['Training', 'trainer_training', 'i-cap', [
                ['My courses', 'trainer.programs.index', ['trainer.programs.*']],
                ['Sessions', 'sessions.index', ['sessions.*']],
                ['Course catalog', 'tesda.index', ['tesda.index', 'tesda.choices.*']],
                ['Scholarships', 'scholarships.index', ['scholarships.*']],
            ]],
            ['Communication', 'trainer_communication', 'i-chat', [
                ['Messages', 'messages.index', ['messages.*']],
                ['Notifications', 'notifications.index', ['notifications.*']],
            ]],
            ['Account', 'trainer_account', 'i-user', [
                ['Profile', 'profile.show', ['profile.*']],
            ]],
        ],
        'youth' => [
            ['Overview', null, 'i-list', [
                ['Overview', 'youth.dashboard', ['youth.dashboard']],
            ]],
            ['Training', 'youth_training', 'i-cap', [
                ['Courses', 'tesda.index', ['tesda.index', 'tesda.choices.*']],
                ['Scholarships', 'scholarships.index', ['scholarships.*']],
                ['Training dates', 'sessions.index', ['sessions.*']],
                ['My requests', 'applications.index', ['applications.*']],
                ['My results', 'assessments.index', ['assessments.*']],
            ]],
            ['Work', 'youth_work', 'i-briefcase', [
                ['Find jobs', 'opportunities.index', ['opportunities.index']],
                ['PESO AI', 'peso.index', ['peso.*']],
                ['Document Scanner', 'scanner.index', ['scanner.*']],
            ]],
            ['Communication', 'youth_communication', 'i-chat', [
                ['Messages', 'messages.index', ['messages.*']],
                ['Notifications', 'notifications.index', ['notifications.*']],
            ]],
            ['Account', 'youth_account', 'i-user', [
                ['Profile', 'profile.show', ['profile.*']],
            ]],
        ],
    ];

    private const ADMIN_TABS = [
        ['Overview', 'admin.dashboard', 'i-list', 'Home', ['admin.dashboard'], null],
        ['Users', 'admin.users.index', 'i-users', 'People', ['admin.users.*'], 'users'],
        ['Jobs', 'opportunities.index', 'i-briefcase', 'Work and careers', ['opportunities.index', 'admin.opportunities.*', 'peso.index'], 'jobs'],
        ['Training', 'centers.index', 'i-cap', 'Training', ['centers.*', 'center-trainers.*', 'sessions.*', 'applications.*', 'assessments.*'], 'training'],
        ['Programs', 'scholarships.index', 'i-cap', 'Training', ['scholarships.*', 'tesda.index', 'tesda.choices.*'], 'programs'],
        ['Inbox', 'messages.index', 'i-chat', 'Communication', ['messages.*', 'notifications.*'], 'inbox'],
        ['Document Scanner', 'scanner.index', 'i-scan', 'Tools', ['scanner.*', 'document-scans.*'], null],
    ];

    private const ADMIN_PAGE_TABS = [
        'users' => [
            ['All users', 'admin.users.index', ['admin.users.*']],
        ],
        'jobs' => [
            ['Openings', 'opportunities.index', ['opportunities.index', 'admin.opportunities.*']],
            ['PESO', 'peso.index', ['peso.index']],
        ],
        'training' => [
            ['Centers', 'centers.index', ['centers.*']],
            ['Trainers', 'center-trainers.index', ['center-trainers.*']],
            ['Dates', 'sessions.index', ['sessions.*']],
            ['Requests', 'applications.index', ['applications.*']],
            ['Results', 'assessments.index', ['assessments.*']],
        ],
        'programs' => [
            ['Scholarships', 'scholarships.index', ['scholarships.*']],
            ['TESDA', 'tesda.index', ['tesda.index', 'tesda.choices.*']],
        ],
        'inbox' => [
            ['Messages', 'messages.index', ['messages.*']],
            ['Notifications', 'notifications.index', ['notifications.*']],
        ],
    ];

    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $isAdmin = $user->role->value === 'admin';
        $roleGroups = self::ROLE_GROUPS[$user->role->value] ?? null;
        $isGroupedWorkspace = $isAdmin || $roleGroups !== null;
        $tabs = $isAdmin
            ? collect(self::ADMIN_TABS)->map(fn (array $tab): array => [
                'label' => $tab[0],
                'group' => $tab[3],
                'href' => route($tab[1]),
                'icon' => $tab[2],
                'active' => request()->routeIs(...$tab[4]),
                'page' => $tab[5],
            ])->all()
            : ($roleGroups !== null
                ? collect($roleGroups)->map(fn (array $group): array => [
                    'label' => $group[0],
                    'group' => null,
                    'href' => route($group[3][0][1]),
                    'icon' => $group[2],
                    'active' => collect($group[3])->contains(fn (array $tab): bool => request()->routeIs(...$tab[2])),
                    'page' => $group[1],
                    'pageTabs' => $group[3],
                ])->all()
            : collect(self::TABS[$user->role->value] ?? [])
                ->filter(fn ($tab) => Route::has($tab[1]))
                ->map(function (array $tab): array {
                    $pattern = Str::endsWith($tab[1], '.dashboard') ? $tab[1] : Str::beforeLast($tab[1], '.').'.*';

                    return [
                        'label' => $tab[0],
                        'group' => $this->groupFor($tab[1]),
                        'href' => route($tab[1]),
                        'icon' => $tab[2],
                        'active' => request()->routeIs($pattern),
                        'page' => null,
                    ];
                })
                ->values()->all());

        $activeAdminTab = $isAdmin ? collect($tabs)->firstWhere('active', true) : null;
        $activeWorkspaceTab = collect($tabs)->firstWhere('active', true);
        $activePageKey = $activeAdminTab['page'] ?? $activeWorkspaceTab['page'] ?? null;
        $pageTabDefinitions = $isAdmin
            ? self::ADMIN_PAGE_TABS[$activePageKey] ?? []
            : (($activeWorkspaceTab['page'] ?? null) ? $activeWorkspaceTab['pageTabs'] : []);
        $adminPageTabs = collect($pageTabDefinitions)
            ->map(fn (array $tab): array => [
                'label' => $tab[0],
                'href' => route($tab[1]),
                'active' => request()->routeIs(...$tab[2]),
            ])
            ->values()->all();

        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
        $currentTab = collect($adminPageTabs)->firstWhere('active', true)
            ?? collect($tabs)->firstWhere('active', true);

        $view->with([
            'navTabs' => $tabs,
            'adminPageTabs' => $adminPageTabs,
            'isAdminWorkspace' => $isAdmin,
            'isGroupedWorkspace' => $isGroupedWorkspace,
            'showFloatingChatButton' => in_array($user->role->value, ['trainer', 'youth'], true),
            'navCurrentLabel' => $currentTab['label'] ?? (request()->routeIs('tuklasbot.index') ? 'TuklasBot' : null),
            'unreadNotificationCount' => $user->unreadNotifications()->count(),
            'navUser' => [
                'name' => $user->name,
                'email' => $user->email,
                'initials' => $initials,
                'photo' => $user->profile_photo_path ? $user->profile_photo_url : null,
                'role' => $user->role->label(),
            ],
        ]);
    }

    private function groupFor(string $route): string
    {
        return match (true) {
            str_contains($route, 'dashboard') => 'Home',
            str_contains($route, 'users') => 'People',
            str_contains($route, 'opportunities') || str_contains($route, 'careers') || str_contains($route, 'peso') => 'Work',
            str_contains($route, 'scanner') => 'Work',
            str_contains($route, 'messages') || str_contains($route, 'notifications') || str_contains($route, 'tuklasbot') => 'Communication',
            str_contains($route, 'profile') => 'Account',
            default => 'Training',
        };
    }
}
