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
            ['Employers', 'admin.employers.index', 'i-users'],
            ['Jobs', 'opportunities.index', 'i-briefcase'],
            ['Scholarships', 'scholarships.index', 'i-cap'],
            ['Centers', 'centers.index', 'i-cap'],
            ['Trainers', 'center-trainers.index', 'i-users'],
            ['Sessions', 'sessions.index', 'i-cap'],
            ['Applications', 'applications.index', 'i-list'],
            ['Assessments', 'assessments.index', 'i-list'],
            ['PESO', 'peso.index', 'i-briefcase'],
            ['AI Scanner', 'scanner.index', 'i-scan'],
            ['TuklasBot', 'tuklasbot.index', 'i-chat'],
            ['Messages', 'messages.index', 'i-chat'],
            ['TESDA', 'tesda.index', 'i-cap'],
        ],
        'trainer' => [
            ['Overview', 'trainer.dashboard', 'i-list'],
            ['Teaching', 'trainer.programs.index', 'i-cap'],
            ['Sessions', 'sessions.index', 'i-cap'],
            ['Scholarships', 'scholarships.index', 'i-cap'],
            ['PESO', 'peso.index', 'i-briefcase'],
            ['AI Scanner', 'scanner.index', 'i-scan'],
            ['TuklasBot', 'tuklasbot.index', 'i-chat'],
            ['Messages', 'messages.index', 'i-chat'],
            ['TESDA', 'tesda.index', 'i-cap'],
        ],
        'tesda_admin' => [
            ['Overview', 'trainer.dashboard', 'i-list'],
            ['Centers', 'centers.index', 'i-cap'],
            ['Programs', 'trainer.programs.index', 'i-cap'],
            ['Trainers', 'center-trainers.index', 'i-users'],
            ['Sessions', 'sessions.index', 'i-cap'],
            ['Applications', 'applications.index', 'i-list'],
            ['Assessments', 'assessments.index', 'i-list'],
            ['Scholarships', 'scholarships.index', 'i-cap'],
            ['Messages', 'messages.index', 'i-chat'],
        ],
        'center_representative' => [
            ['Overview', 'trainer.dashboard', 'i-list'],
            ['My center', 'centers.index', 'i-cap'],
            ['Programs', 'trainer.programs.index', 'i-cap'],
            ['Trainers', 'center-trainers.index', 'i-users'],
            ['Sessions', 'sessions.index', 'i-cap'],
            ['Trainees', 'applications.index', 'i-users'],
            ['Assessments', 'assessments.index', 'i-list'],
            ['Messages', 'messages.index', 'i-chat'],
        ],
        'youth' => [
            ['Overview', 'youth.dashboard', 'i-list'],
            ['PESO', 'peso.index', 'i-briefcase'],
            ['Jobs', 'opportunities.index', 'i-briefcase'],
            ['Scholarships', 'scholarships.index', 'i-cap'],
            ['Sessions', 'sessions.index', 'i-cap'],
            ['Applications', 'applications.index', 'i-list'],
            ['Assessments', 'assessments.index', 'i-list'],
            ['AI Scanner', 'scanner.index', 'i-scan'],
            ['TuklasBot', 'tuklasbot.index', 'i-chat'],
            ['Messages', 'messages.index', 'i-chat'],
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
            ->push(['Notifications', 'notifications.index', 'i-list'])
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
