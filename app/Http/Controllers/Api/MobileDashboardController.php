<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
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
    private function youthDashboard(User $user): array
    {
        $user->loadMissing('youthProfile');
        $completion = ProfileCompletion::for($user);
        $scans = $user->documentScans();
        $recentScans = (clone $scans)->latest()->limit(6)->get();

        return [
            'role' => Role::Youth->value,
            'title' => 'Hi, '.explode(' ', trim($user->name))[0],
            'stats' => [
                $this->stat('Profile strength', $completion['percent'].'%', 'Complete it for better guidance'),
                $this->stat('Documents scanned', (clone $scans)->where('status', 'done')->count(), 'Resumes and certificates'),
                $this->stat('Skills on profile', count($user->youthProfile?->skills ?? []), 'Added by you or from scans'),
            ],
            'chart' => [
                'kind' => 'meters',
                'title' => 'Profile breakdown',
                'items' => $completion['items'],
                'insight' => $completion['percent'] >= 100
                    ? 'Your profile is complete. Guidance works best with up-to-date details.'
                    : 'The more you share, the better your guidance. Suggestions are not guarantees of jobs, admission, or training slots.',
            ],
            'recent' => $recentScans->map(fn (DocumentScan $scan): array => [
                'primary' => $scan->original_name,
                'secondary' => ucfirst($scan->doc_type).' · '.round($scan->size / 1024).' KB',
                'date' => $scan->created_at->toDateString(),
                'status' => $scan->status,
            ])->all(),
            'tasks' => [
                ['title' => 'Complete your profile', 'meta' => $completion['percent'].'% done', 'destination' => 'profile', 'done' => $completion['percent'] >= 100],
                ['title' => 'Scan a resume or certificate', 'meta' => 'Optional', 'destination' => 'scanner', 'done' => false],
                ['title' => 'Take the skills assessment', 'meta' => 'Coming soon', 'destination' => null, 'done' => false],
                ['title' => 'See career and training suggestions', 'meta' => 'Coming soon', 'destination' => null, 'done' => false],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function trainerDashboard(User $user): array
    {
        $programs = TrainingProgram::query()->where('created_by', $user->id);
        $total = (clone $programs)->count();
        $published = (clone $programs)->where('status', 'published')->count();
        $drafts = (clone $programs)->where('status', 'draft')->count();

        return [
            'role' => Role::Trainer->value,
            'title' => 'TESDA Lingayen overview',
            'stats' => [
                $this->stat('My programs', $total, 'All statuses'),
                $this->stat('Published', $published, 'Visible to youth'),
                $this->stat('Drafts', $drafts, 'Not visible yet'),
            ],
            'chart' => [
                'kind' => 'meters',
                'title' => 'Programs by status',
                'items' => [
                    ['label' => 'Published', 'pct' => $this->percentage($published, $total)],
                    ['label' => 'Draft', 'pct' => $this->percentage($drafts, $total)],
                    ['label' => 'Archived', 'pct' => $this->percentage((clone $programs)->where('status', 'archived')->count(), $total)],
                ],
                'insight' => $total === 0
                    ? 'You have not added a program yet. Only programs you publish can be recommended to youth.'
                    : 'Keep published programs verified so youth see current information.',
            ],
            'recent' => (clone $programs)->latest('updated_at')->limit(6)->get()->map(fn (TrainingProgram $program): array => [
                'primary' => $program->title,
                'secondary' => $program->nc_level ?: 'Training program',
                'date' => $program->updated_at->toDateString(),
                'status' => $program->status,
            ])->all(),
            'tasks' => [
                ['title' => 'Review published programs', 'meta' => $published.' published', 'destination' => 'tesda', 'done' => $published > 0],
                ['title' => 'Program management', 'meta' => 'Not available in this release', 'destination' => null, 'done' => false],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function adminDashboard(): array
    {
        $youth = User::query()->where('role', Role::Youth->value);
        $since = now()->subDays(13)->startOfDay();
        $perDay = (clone $youth)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
        $trend = [];

        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $day = now()->subDays($daysAgo);
            $trend[] = [
                'label' => $day->format('M j'),
                'value' => (int) ($perDay[$day->toDateString()] ?? 0),
            ];
        }

        $youthTotal = (clone $youth)->count();
        $trainerTotal = User::where('role', Role::Trainer->value)->count();
        $published = TrainingProgram::published()->count();

        return [
            'role' => Role::SuperAdmin->value,
            'title' => 'PESO Bugallon overview',
            'stats' => [
                $this->stat('Youth accounts', $youthTotal, 'Last 14 days'),
                $this->stat('Trainer accounts', $trainerTotal, 'TESDA Lingayen'),
                $this->stat('Published programs', $published, 'Available in the local catalog'),
            ],
            'chart' => [
                'kind' => 'bars',
                'title' => 'New youth accounts',
                'items' => $trend,
                'insight' => $youthTotal === 0
                    ? 'No youth have signed up yet. Accounts will appear here as people register.'
                    : 'Youth accounts created during the last 14 days.',
            ],
            'recent' => (clone $youth)->with('youthProfile')->latest()->limit(6)->get()->map(fn (User $member): array => [
                'primary' => $member->name,
                'secondary' => $member->youthProfile?->barangay ?: 'Barangay not set',
                'date' => $member->created_at->toDateString(),
                'status' => $member->hasVerifiedEmail() ? 'verified' : 'unverified',
            ])->all(),
            'tasks' => [
                ['title' => 'Trainer accounts', 'meta' => $trainerTotal.' created', 'destination' => null, 'done' => $trainerTotal > 0],
                ['title' => 'Publish TESDA Lingayen programs', 'meta' => $published.' published', 'destination' => 'tesda', 'done' => $published > 0],
                ['title' => 'Youth sign-ups', 'meta' => $youthTotal.' accounts', 'destination' => null, 'done' => $youthTotal > 0],
            ],
        ];
    }

    /** @return array{label: string, value: int|string, note: string} */
    private function stat(string $label, int|string $value, string $note): array
    {
        return ['label' => $label, 'value' => $value, 'note' => $note];
    }

    private function percentage(int $value, int $total): int
    {
        return $total > 0 ? (int) round($value / $total * 100) : 0;
    }
}
