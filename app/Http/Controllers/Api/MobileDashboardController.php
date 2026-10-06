<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use App\Models\DocumentScan;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\ProfileCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(match ($user->role) {
            Role::Youth => $this->youthDashboard($user),
            Role::Trainer => $this->trainerDashboard($user),
            Role::SuperAdmin => $this->adminDashboard(),
        });
    }

    /** @return array<string, mixed> */
    private function adminDashboard(): array
    {
        $since = now()->subDays(29)->startOfDay();
        $youth = User::query()->where('role', Role::Youth->value);
        $youthTotal = (clone $youth)->count();
        $youthNew = (clone $youth)->where('created_at', '>=', $since)->count();
        $trainerTotal = User::query()->where('role', Role::Trainer->value)->count();
        $publishedPrograms = TrainingProgram::published()->count();
        $publishedCareers = CareerPath::published()->count();

        $perDay = (clone $youth)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $bars = [];
        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $day = now()->subDays($daysAgo);
            $bars[] = [
                'label' => $day->format('M j'),
                'value' => (int) ($perDay[$day->toDateString()] ?? 0),
            ];
        }
        $bars = $this->scaleBars($bars);

        $latestYouth = (clone $youth)
            ->with('youthProfile')
            ->latest()
            ->limit(6)
            ->get();

        return [
            'role' => Role::SuperAdmin->value,
            'title' => 'PESO Bugallon overview',
            'stats' => [
                $this->stat('Youth accounts', $youthTotal, $youthNew > 0 ? '+'.$youthNew : null, 'good', 'Last 30 days'),
                $this->stat('Trainer accounts', $trainerTotal, null, 'neutral', 'TESDA Lingayen'),
                $this->stat('Published programs', $publishedPrograms, null, 'neutral', $publishedCareers.' career paths published'),
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
                'rows' => $latestYouth->map(fn (User $member): array => [
                    [
                        't' => $member->name,
                        's' => $member->date_of_birth ? $member->date_of_birth->age.' years old' : null,
                        'avatar' => $this->initials($member->name),
                    ],
                    ['t' => $member->youthProfile?->barangay ?: 'Not set'],
                    ['t' => $member->created_at->format('M j, Y')],
                    $member->hasVerifiedEmail()
                        ? ['chip' => 'Verified', 'tone' => 'good']
                        : ['chip' => 'Unverified', 'tone' => 'warn'],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'Setup checklist',
                'items' => [
                    $this->task('Create a TESDA Lingayen trainer account', $trainerTotal.' created', 'Trainers maintain the training catalog.', $trainerTotal > 0),
                    $this->task('Publish TESDA Lingayen programs', $publishedPrograms.' published', 'Only programs in the system can be recommended.', $publishedPrograms > 0),
                    $this->task('Publish career paths', $publishedCareers.' published', 'Recommendations draw on these.', $publishedCareers > 0),
                    $this->task('Youth sign-ups', $youthTotal.' accounts', 'Share the site with Bugallon youth.', $youthTotal > 0),
                ],
            ],
            'actions' => $this->actions([
                ['Manage users', null, 'users'],
                ['Career paths', null, 'briefcase'],
                ['Training catalog', null, 'cap'],
                ['Edit profile', 'profile', 'user'],
                ['Scanner', 'scanner', 'scan'],
                ['PESO job matches', 'peso', 'briefcase'],
                ['TESDA', 'tesda', 'cap'],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function trainerDashboard(User $user): array
    {
        $programs = TrainingProgram::query()->where('created_by', $user->id);
        $total = (clone $programs)->count();
        $published = (clone $programs)->where('status', 'published')->count();
        $drafts = (clone $programs)->where('status', 'draft')->count();
        $archived = (clone $programs)->where('status', 'archived')->count();
        $stale = (clone $programs)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('last_verified_at')
                ->orWhere('last_verified_at', '<', now()->subDays(90)))
            ->count();
        $recent = (clone $programs)->latest('updated_at')->limit(6)->get();

        return [
            'role' => Role::Trainer->value,
            'title' => 'TESDA Lingayen overview',
            'stats' => [
                $this->stat('My programs', $total, null, 'neutral', 'All statuses'),
                $this->stat('Published', $published, null, 'neutral', 'Visible to youth'),
                $this->stat('Drafts', $drafts, $drafts > 0 ? $drafts.' to finish' : null, 'warn', 'Not visible yet'),
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
                    ['label' => 'Published', 'pct' => $this->percentage($published, $total)],
                    ['label' => 'Draft', 'pct' => $this->percentage($drafts, $total)],
                    ['label' => 'Archived', 'pct' => $this->percentage($archived, $total)],
                ],
            ],
            'table' => [
                'title' => 'Recent programs',
                'columns' => ['Program', 'NC level', 'Updated', 'Status'],
                'empty' => 'No programs yet. Programs you add will be listed here.',
                'rows' => $recent->map(fn (TrainingProgram $program): array => [
                    [
                        't' => $program->title,
                        's' => $program->duration_hours ? $program->duration_hours.' hours' : null,
                        'avatar' => $this->initials($program->title),
                    ],
                    ['t' => $program->nc_level ?: 'Not set'],
                    ['t' => $program->updated_at->format('M j, Y')],
                    [
                        'chip' => ucfirst($program->status),
                        'tone' => match ($program->status) {
                            'published' => 'good',
                            'draft' => 'warn',
                            default => 'neutral',
                        },
                    ],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'To do',
                'items' => [
                    $this->task('Add your first program', $total.' added', 'Enter the TVET programs TESDA Lingayen offers.', $total > 0),
                    $this->task('Publish drafts', $drafts.' drafts', 'Drafts are hidden from youth.', $total > 0 && $drafts === 0),
                    $this->task('Re-verify older programs', $stale.' need review', 'Confirm details at least every 90 days.', $stale === 0 && $published > 0),
                ],
            ],
            'actions' => $this->actions([
                ['My programs', null, 'cap'],
                ['Add a program', null, 'clipboard'],
                ['Edit profile', 'profile', 'user'],
                ['Scanner', 'scanner', 'scan'],
                ['PESO job matches', 'peso', 'briefcase'],
                ['TESDA', 'tesda', 'cap'],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function youthDashboard(User $user): array
    {
        $user->loadMissing('youthProfile');
        $completion = ProfileCompletion::for($user);
        $scans = $user->documentScans();
        $scanned = (clone $scans)->where('status', 'done')->count();
        $skills = count($user->youthProfile?->skills ?? []);
        $recent = (clone $scans)->latest()->limit(6)->get();
        $latestAnalysis = (clone $scans)
            ->where('status', 'done')
            ->latest()
            ->value('result') ?? [];

        return [
            'role' => Role::Youth->value,
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
                'job_recommendations' => $latestAnalysis['jobRecommendations'] ?? [],
                'skill_gaps' => $latestAnalysis['skillGaps'] ?? [],
                'learning_recommendations' => $latestAnalysis['learningRecommendations'] ?? [],
            ],
            'chart' => [
                'kind' => 'meters',
                'title' => 'Profile breakdown',
                'range' => 'Right now',
                'legend' => [['tone' => 'violet', 'label' => 'Completed']],
                'insight' => $completion['percent'] >= 100
                    ? 'Your profile is complete. Guidance works best with up-to-date details.'
                    : 'The more you share, the better your guidance. Suggestions are not guarantees of jobs, admission, or training slots.',
                'cta' => ['label' => 'Edit profile', 'href' => 'profile'],
                'items' => $completion['items'],
            ],
            'table' => [
                'title' => 'Recent scans',
                'columns' => ['File', 'Type', 'Date', 'Status'],
                'empty' => 'No scans yet. Scanned resumes and certificates will be listed here.',
                'rows' => $recent->map(fn (DocumentScan $scan): array => [
                    [
                        't' => $scan->original_name,
                        's' => round($scan->size / 1024).' KB',
                        'avatar' => $this->initials($scan->original_name),
                    ],
                    ['t' => ucfirst($scan->doc_type)],
                    ['t' => $scan->created_at->format('M j, Y')],
                    [
                        'chip' => ucfirst($scan->status),
                        'tone' => match ($scan->status) {
                            'done' => 'good',
                            'failed' => 'bad',
                            default => 'warn',
                        },
                    ],
                ])->all(),
            ],
            'tasks' => [
                'title' => 'Next steps',
                'items' => [
                    $this->task('Complete your profile', $completion['percent'].'% done', 'Schooling, skills, and interests shape your guidance.', $completion['percent'] >= 100, 'profile'),
                    $this->task('Scan a resume or certificate', $scanned.' scanned', 'Let Google Gemini suggest skills and credentials.', $scanned > 0, 'scanner'),
                    $this->task('Take the skills assessment', 'Coming soon', 'Answer a short set of questions.', false),
                    $this->task('See PESO job matches', $scanned > 0 ? 'View your career matches' : 'Scan a resume to get matches', 'Explore roles suggested from your scanned resume and certificates.', false, 'peso'),
                ],
            ],
            'actions' => $this->actions([
                ['Edit profile', 'profile', 'user'],
                ['Scanner', 'scanner', 'scan'],
                ['PESO job matches', 'peso', 'briefcase'],
                ['Trainings', 'tesda', 'cap'],
            ]),
        ];
    }

    /** @return array{label: string, value: int|string, delta: ?string, tone: string, note: string} */
    private function stat(string $label, int|string $value, ?string $delta, string $tone, string $note): array
    {
        return ['label' => $label, 'value' => $value, 'delta' => $delta, 'tone' => $tone, 'note' => $note];
    }

    /** @return array{title: string, meta: string, desc: string, state: string, href: ?string} */
    private function task(string $title, string $meta, string $description, bool $done, ?string $href = null): array
    {
        return [
            'title' => $title,
            'meta' => $meta,
            'desc' => $description,
            'state' => $done ? 'done' : 'todo',
            'href' => $href,
        ];
    }

    /** @param array<int, array{0: string, 1: ?string, 2: string}> $definitions
     * @return array<int, array{label: string, href: ?string, icon: string}>
     */
    private function actions(array $definitions): array
    {
        return array_map(fn (array $definition): array => [
            'label' => $definition[0],
            'href' => $definition[1],
            'icon' => $definition[2],
        ], $definitions);
    }

    /** @param array<int, array{label: string, value: int}> $bars
     * @return array<int, array{label: string, value: int, h: int}>
     */
    private function scaleBars(array $bars): array
    {
        $max = max(1, max(array_column($bars, 'value')));

        return array_map(fn (array $bar): array => $bar + [
            'h' => max(4, (int) round($bar['value'] / $max * 100)),
        ], $bars);
    }

    private function initials(string $text): string
    {
        return collect(preg_split('/[\s._-]+/', trim($text)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    private function percentage(int $value, int $total): int
    {
        return $total > 0 ? (int) round($value / $total * 100) : 0;
    }
}
