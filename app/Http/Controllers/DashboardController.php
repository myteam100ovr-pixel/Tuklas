<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\CareerPath;
use App\Models\DocumentScan;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentScanHistory;
use App\Services\TesdaQualificationCatalog;
use App\Support\ProfileCompletion;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly TesdaQualificationCatalog $catalog) {}

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

        $latest = User::query()
            ->latest()
            ->take(6)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);

        return view('dashboard.show', [
            'title' => 'PESO Bugallon overview',
            'nationalCatalog' => $this->catalog->all(),
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
                'title' => 'Latest registered accounts',
                'columns' => ['Name', 'Role', 'Account status', 'Joined'],
                'empty' => 'No user accounts have been registered yet.',
                'rows' => $latest->map(fn (User $u) => [
                    ['t' => $u->name, 's' => $u->email, 'avatar' => $this->initials($u->name)],
                    ['t' => $u->role->label()],
                    $u->is_active ? ['chip' => 'Active', 'tone' => 'good'] : ['chip' => 'Inactive', 'tone' => 'bad'],
                    ['t' => $u->created_at->format('M j, Y')],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'Setup checklist',
                'items' => [
                    $this->task('Create a TESDA Lingayen trainer account', $trainers.' created', 'Trainers maintain the training catalog.', $trainers > 0),
                    $this->task('Publish TESDA Lingayen programs', $published.' published', 'Only programs in the system can be recommended.', $published > 0),
                    $this->task('Publish career paths', $careers.' published', 'Published paths are available for members to explore.', $careers > 0, route('admin.careers.index')),
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
        $user = auth()->user();
        $mine = TrainingProgram::query();
        if ($user->hasRole(Role::CenterRepresentative)) {
            abort_unless($user->training_center_id, 403);
            $mine->where('training_center_id', $user->training_center_id);
        } elseif (! $user->hasRole(Role::TesdaAdmin)) {
            $mine->where('created_by', $user->id);
        }
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
            'title' => $user->hasRole(Role::CenterRepresentative) ? 'Training center overview' : 'TESDA training overview',
            'nationalCatalog' => $this->catalog->all(),
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
                    ['label' => 'Published', 'pct' => $pct($published), 'total' => $total, 'detail' => $published.' of '.$total.' programs'],
                    ['label' => 'Draft', 'pct' => $pct($draft), 'total' => $total, 'detail' => $draft.' of '.$total.' programs'],
                    ['label' => 'Archived', 'pct' => $pct($archived), 'total' => $total, 'detail' => $archived.' of '.$total.' programs'],
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

    public function youth(DocumentScanHistory $scanHistory): View
    {
        $user = auth()->user()->load('youthProfile');
        $completion = ProfileCompletion::for($user);
        $scans = DocumentScan::where('user_id', $user->id);
        $scanned = (clone $scans)->where('status', 'done')->count();
        $skills = count($user->youthProfile?->skills ?? []);
        $recent = (clone $scans)->latest()->take(6)->get();
        $latestAnalysis = (clone $scans)->where('status', 'done')->latest('processed_at')->value('result') ?? [];
        $scanRecommendations = $scanHistory->recommendationsFor($user);
        $careerMatches = collect($latestAnalysis['careerMatches'] ?? $latestAnalysis['job_roles'] ?? [])
            ->map(fn ($match): array => is_array($match)
                ? ['name' => $match['name'] ?? $match['title'] ?? null, 'skillCoverage' => $match['skillCoverage'] ?? null, 'evidencedSkills' => $match['evidencedSkills'] ?? [], 'skillsToDevelop' => $match['skillsToDevelop'] ?? []]
                : ['name' => $match, 'skillCoverage' => null, 'evidencedSkills' => [], 'skillsToDevelop' => []])
            ->filter(fn (array $match): bool => is_string($match['name']) && trim($match['name']) !== '')
            ->values()
            ->all();
        $jobRecommendations = collect($latestAnalysis['jobRecommendations'] ?? [])
            ->filter(fn ($recommendation): bool => is_array($recommendation))
            ->map(fn (array $recommendation): array => [
                'title' => $recommendation['title'] ?? null,
                'reason' => $recommendation['reason'] ?? null,
                'evidence' => $recommendation['evidence'] ?? null,
                'skillCoverage' => $recommendation['skillCoverage'] ?? null,
                'evidencedSkills' => $recommendation['evidencedSkills'] ?? [],
                'skillsToDevelop' => $recommendation['skillsToDevelop'] ?? [],
            ])
            ->filter(fn (array $recommendation): bool => is_string($recommendation['title']) && trim($recommendation['title']) !== '')
            ->take(6)
            ->values()
            ->all();
        $skillGaps = collect($latestAnalysis['skillGaps'] ?? [])
            ->filter(fn ($skillGap): bool => is_string($skillGap) && trim($skillGap) !== '')
            ->values()
            ->all();
        $learningRecommendations = collect($latestAnalysis['learningRecommendations'] ?? [])
            ->filter(fn ($recommendation): bool => is_array($recommendation))
            ->values()
            ->all();
        $tesdaRecommendations = collect($latestAnalysis['tesdaRecommendations'] ?? [])
            ->filter(fn ($recommendation): bool => is_string($recommendation) && trim($recommendation) !== '')
            ->values();

        if ($tesdaRecommendations->isEmpty()) {
            $tesdaRecommendations = collect($latestAnalysis['tesda_training'] ?? [])
                ->filter(fn ($recommendation): bool => is_string($recommendation) && trim($recommendation) !== '')
                ->values();
        }

        return view('dashboard.show', [
            'title' => 'Hi, '.explode(' ', trim($user->name))[0],
            'nationalCatalog' => $this->catalog->all(),
            'stats' => [
                $this->stat('Profile completion', $completion['percent'].'%', null, 'neutral', $completion['completed'].' of '.$completion['total'].' details added'),
                $this->stat('Documents scanned', $scanned, null, 'neutral', 'Resumes and certificates'),
                $this->stat('Skills on profile', $skills, null, 'neutral', 'Added by you or from scans'),
            ],
            'profileInsights' => [
                'skills' => $user->youthProfile?->skills ?? [],
                'credentials' => $user->youthProfile?->credentials ?? [],
                'job_roles' => $careerMatches,
                'tesda_training' => $tesdaRecommendations->all(),
                'has_scan' => $scanned > 0,
                'job_recommendations' => $jobRecommendations,
                'skill_gaps' => $skillGaps,
                'learning_recommendations' => $learningRecommendations,
                'scan_recommendations' => $scanRecommendations,
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
                    $this->task('Take the skills assessment', $user->youthProfile?->assessment_preferences !== null ? 'Saved · Update anytime' : 'Not saved yet', 'Reflect on your skills and training interests.', $user->youthProfile?->assessment_preferences !== null, route('youth.assessment.index')),
                    $this->task('See PESO job matches', $scanned.' documents scanned', 'View job suggestions based on your completed resume and certificate scans.', $scanned > 0, route('peso.index')),
                ])),
            ],
            'actions' => $this->actions([
                ['Edit profile', 'profile.show', 'user'],
                ['Scanner', 'scanner.index', 'scan'],
                ['Careers', 'careers.index', 'briefcase'],
                ['Trainings', 'tesda.index', 'cap'],
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

        return array_map(fn ($b) => $b + ['h' => (int) round($b['value'] / $max * 100)], $bars);
    }

    private function initials(string $text): string
    {
        return collect(preg_split('/[\s._-]+/', trim($text)))
            ->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }
}
