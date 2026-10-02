<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\CareerPath;
use App\Models\DocumentScan;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\ProfileCompletion;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        $since = now()->subDays(29)->startOfDay();

        $youthTotal = User::where('role', Role::Youth->value)->count();
        $youthNew = User::where('role', Role::Youth->value)->where('created_at', '>=', $since)->count();
        $trainers = User::where('role', Role::Trainer->value)->count();
        $published = TrainingProgram::published()->count();
        $careers = CareerPath::published()->count();

        $perDay = User::where('role', Role::Youth->value)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $bars = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $bars[] = ['label' => $day->format('M j'), 'value' => (int) ($perDay[$day->toDateString()] ?? 0)];
        }
        $bars = $this->scaleBars($bars);

        $latest = User::where('role', Role::Youth->value)->with('youthProfile')->latest()->take(6)->get();

        return view('dashboard.show', [
            'title' => 'PESO Bugallon overview',
            'stats' => [
                $this->stat('Youth accounts', $youthTotal, $youthNew > 0 ? '+'.$youthNew : null, 'good', 'Last 30 days'),
                $this->stat('Trainer accounts', $trainers, null, 'neutral', 'TESDA Lingayen'),
                $this->stat('Published programs', $published, null, 'neutral', $careers.' career paths published'),
            ],
            'chart' => [
                'kind' => 'bars',
                'title' => 'New youth accounts',
                'range' => 'Last 30 days',
                'legend' => [['tone' => 'violet', 'label' => 'Accounts created per day']],
                'insight' => $youthTotal === 0
                    ? 'No youth have signed up yet. Accounts will appear here as people register.'
                    : $youthNew.' new '.str('account')->plural($youthNew).' in the last 30 days.',
                'bars' => $bars,
                'axis' => [$bars[0]['label'], $bars[29]['label']],
            ],
            'table' => [
                'title' => 'Latest youth accounts',
                'columns' => ['Name', 'Barangay', 'Joined', 'Email'],
                'empty' => 'No youth accounts yet.',
                'rows' => $latest->map(fn (User $u) => [
                    ['t' => $u->name, 's' => $u->date_of_birth ? $u->date_of_birth->age.' years old' : null, 'avatar' => $this->initials($u->name)],
                    ['t' => $u->youthProfile?->barangay ?: 'Not set'],
                    ['t' => $u->created_at->format('M j, Y')],
                    $u->email_verified_at ? ['chip' => 'Verified', 'tone' => 'good'] : ['chip' => 'Unverified', 'tone' => 'warn'],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'Setup checklist',
                'items' => [
                    $this->task('Create a TESDA Lingayen trainer account', $trainers.' created', 'Trainers maintain the training catalog.', $trainers > 0),
                    $this->task('Publish TESDA Lingayen programs', $published.' published', 'Only programs in the system can be recommended.', $published > 0),
                    $this->task('Publish career paths', $careers.' published', 'Recommendations draw on these.', $careers > 0),
                    $this->task('Youth sign-ups', $youthTotal.' accounts', 'Share the site with Bugallon youth.', $youthTotal > 0),
                ],
            ],
            'actions' => $this->actions([
                ['Manage users', 'admin.users.index', 'users'],
                ['Career paths', 'admin.careers.index', 'briefcase'],
                ['Training catalog', 'admin.trainings.index', 'cap'],
                ['Edit profile', 'profile.show', 'user'],
            ]),
        ]);
    }

    public function trainer(): View
    {
        $mine = TrainingProgram::where('created_by', auth()->id());
        $total = (clone $mine)->count();
        $published = (clone $mine)->where('status', 'published')->count();
        $draft = (clone $mine)->where('status', 'draft')->count();
        $archived = (clone $mine)->where('status', 'archived')->count();
        $stale = (clone $mine)->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', now()->subDays(90)))
            ->count();
        $recent = (clone $mine)->latest('updated_at')->take(6)->get();

        $pct = fn (int $n) => $total > 0 ? (int) round($n / $total * 100) : 0;

        return view('dashboard.show', [
            'title' => 'TESDA Lingayen overview',
            'stats' => [
                $this->stat('My programs', $total, null, 'neutral', 'All statuses'),
                $this->stat('Published', $published, null, 'neutral', 'Visible to youth'),
                $this->stat('Drafts', $draft, $draft > 0 ? $draft.' to finish' : null, 'warn', 'Not visible yet'),
            ],
            'chart' => [
                'kind' => 'meters',
                'title' => 'Programs by status',
                'range' => 'All time',
                'legend' => [['tone' => 'violet', 'label' => 'Share of my programs']],
                'insight' => $total === 0
                    ? 'You have not added a program yet. Only programs you publish can be recommended to youth.'
                    : 'Keep published programs verified so youth see current information.',
                'items' => [
                    ['label' => 'Published', 'pct' => $pct($published)],
                    ['label' => 'Draft', 'pct' => $pct($draft)],
                    ['label' => 'Archived', 'pct' => $pct($archived)],
                ],
            ],
            'table' => [
                'title' => 'Recent programs',
                'columns' => ['Program', 'NC level', 'Updated', 'Status'],
                'empty' => 'No programs yet. Programs you add will be listed here.',
                'rows' => $recent->map(fn (TrainingProgram $p) => [
                    ['t' => $p->title, 's' => $p->duration_hours ? $p->duration_hours.' hours' : null, 'avatar' => $this->initials($p->title)],
                    ['t' => $p->nc_level ?: 'Not set'],
                    ['t' => $p->updated_at->format('M j, Y')],
                    ['chip' => ucfirst($p->status), 'tone' => $p->status === 'published' ? 'good' : ($p->status === 'draft' ? 'warn' : 'neutral')],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'To do',
                'items' => [
                    $this->task('Add your first program', $total.' added', 'Enter the TVET programs TESDA Lingayen offers.', $total > 0),
                    $this->task('Publish drafts', $draft.' drafts', 'Drafts are hidden from youth.', $total > 0 && $draft === 0),
                    $this->task('Re-verify older programs', $stale.' need review', 'Confirm details at least every 90 days.', $stale === 0 && $published > 0),
                ],
            ],
            'actions' => $this->actions([
                ['My programs', 'trainer.programs.index', 'cap'],
                ['Add a program', 'trainer.programs.create', 'clipboard'],
                ['Edit profile', 'profile.show', 'user'],
            ]),
        ]);
    }

    public function youth(): View
    {
        $user = auth()->user()->load('youthProfile');
        $completion = ProfileCompletion::for($user);
        $scans = DocumentScan::where('user_id', $user->id);
        $scanned = (clone $scans)->where('status', 'done')->count();
        $skills = count($user->youthProfile?->skills ?? []);
        $recent = (clone $scans)->latest()->take(6)->get();
        $latestAnalysis = (clone $scans)->where('status', 'done')->latest()->first(['result'])?->result ?? [];

        return view('dashboard.show', [
            'title' => 'Hi, '.explode(' ', trim($user->name))[0],
            'stats' => [
                $this->stat('Profile strength', $completion['percent'].'%', null, 'neutral', 'Complete it for better guidance'),
                $this->stat('Documents scanned', $scanned, null, 'neutral', 'Resumes and certificates'),
                $this->stat('Skills on profile', $skills, null, 'neutral', 'Added by you or from scans'),
            ],
            'profileInsights' => [
                'skills' => $user->youthProfile?->skills ?? [],
                'credentials' => $user->youthProfile?->credentials ?? [],
                'job_roles' => $latestAnalysis['job_roles'] ?? [],
                'tesda_training' => $latestAnalysis['tesda_training'] ?? [],
            ],
            'chart' => [
                'kind' => 'meters',
                'title' => 'Profile breakdown',
                'range' => 'Right now',
                'legend' => [['tone' => 'violet', 'label' => 'Completed']],
                'insight' => $completion['percent'] >= 100
                    ? 'Your profile is complete. Guidance works best with up-to-date details.'
                    : 'The more you share, the better your guidance. Suggestions are not guarantees of jobs, admission, or training slots.',
                'cta' => ['label' => 'Edit profile', 'href' => route('profile.show')],
                'items' => $completion['items'],
            ],
            'table' => [
                'title' => 'Recent scans',
                'columns' => ['File', 'Type', 'Date', 'Status'],
                'empty' => 'No scans yet. Scanned resumes and certificates will be listed here.',
                'rows' => $recent->map(fn (DocumentScan $s) => [
                    ['t' => $s->original_name, 's' => round($s->size / 1024).' KB', 'avatar' => $this->initials($s->original_name)],
                    ['t' => ucfirst($s->doc_type)],
                    ['t' => $s->created_at->format('M j, Y')],
                    ['chip' => ucfirst($s->status), 'tone' => match ($s->status) {
                        'done' => 'good', 'failed' => 'bad', default => 'warn'
                    }],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'Next steps',
                'items' => array_values(array_filter([
                    $this->task('Complete your profile', $completion['percent'].'% done', 'Schooling, skills, and interests shape your guidance.', $completion['percent'] >= 100, route('profile.show')),
                    Route::has('scanner.index')
                        ? $this->task('Scan a resume or certificate', $scanned.' scanned', 'Let Google Gemini suggest skills and credentials.', $scanned > 0, route('scanner.index'))
                        : null,
                    $this->task('Take the skills assessment', 'Coming soon', 'Answer a short set of questions.', false),
                    $this->task('See career and training suggestions', 'Coming soon', 'Based on TESDA Lingayen programs in the system.', false),
                ])),
            ],
            'actions' => $this->actions([
                ['Edit profile', 'profile.show', 'user'],
                ['Scanner', 'scanner.index', 'scan'],
                ['Careers', 'youth.careers.index', 'briefcase'],
                ['Trainings', 'youth.trainings.index', 'cap'],
            ]),
        ]);
    }

    private function stat(string $label, int|string $value, ?string $delta, string $tone, string $note): array
    {
        return ['label' => $label, 'value' => $value, 'delta' => $delta, 'tone' => $tone, 'note' => $note];
    }

    private function task(string $title, string $meta, string $desc, bool $done, ?string $href = null): array
    {
        return ['title' => $title, 'meta' => $meta, 'desc' => $desc, 'state' => $done ? 'done' : 'todo', 'href' => $href];
    }

    private function actions(array $defs): array
    {
        return array_map(fn ($d) => [
            'label' => $d[0],
            'href' => Route::has($d[1]) ? route($d[1]) : null,
            'icon' => $d[2],
        ], $defs);
    }

    private function scaleBars(array $bars): array
    {
        $max = max(1, max(array_column($bars, 'value')));

        return array_map(fn ($b) => $b + ['h' => max(4, (int) round($b['value'] / $max * 100))], $bars);
    }

    private function initials(string $text): string
    {
        return collect(preg_split('/[\s._-]+/', trim($text)))
            ->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }
}
