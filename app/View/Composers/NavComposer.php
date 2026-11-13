<?php

namespace App\View\Composers;

use Illuminate\View\View;

class NavComposer
{
    private const ROLE_GROUPS = [
        'tesda_admin' => [
            ['Overview', null, 'i-list', [
                ['Overview', 'trainer.dashboard', ['trainer.dashboard']],
            ]],
            ['Training', 'tesda_training', 'i-cap', [
                ['Centers', 'centers.index', ['centers.*']],
                ['My courses', 'trainer.programs.index', ['trainer.programs.*']],
                ['Course catalog', 'tesda.index', ['tesda.index', 'tesda.choices.*']],
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
        ['Jobs', 'opportunities.index', 'i-briefcase', 'Work and careers', ['opportunities.index', 'admin.opportunities.*', 'admin.job-applications.*', 'peso.index'], 'jobs'],
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
            ['Applications', 'admin.job-applications.index', ['admin.job-applications.*']],
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
            : collect($roleGroups ?? [])->map(fn (array $group): array => [
                'label' => $group[0],
                'group' => null,
                'href' => route($group[3][0][1]),
                'icon' => $group[2],
                'active' => collect($group[3])->contains(fn (array $tab): bool => request()->routeIs(...$tab[2])),
                'page' => $group[1],
                'pageTabs' => $group[3],
            ])->all();

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
            'showFloatingChatButton' => in_array($user->role->value, ['tesda_admin', 'youth'], true),
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
}
