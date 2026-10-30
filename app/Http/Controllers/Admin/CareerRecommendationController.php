<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $isPESOAdmin = $user->hasRole(Role::SuperAdmin);
        $hasRecommendations = fn (Builder|HasMany $query): Builder|HasMany => $query
            ->where('status', 'done')
            ->whereJsonLength('result->jobRecommendations', '>', 0);

        if ($isPESOAdmin) {
            $recommendationPages = User::query()
                ->select(['id', 'name'])
                ->where('role', Role::Youth->value)
                ->whereHas('documentScans', $hasRecommendations)
                ->with(['documentScans' => fn (HasMany $query): HasMany => $hasRecommendations($query)->latest('processed_at')])
                ->withMax(['documentScans as latest_scan_at' => $hasRecommendations], 'processed_at')
                ->orderByDesc('latest_scan_at')
                ->orderBy('id')
                ->paginate(12);

            $recommendationGroups = $recommendationPages->getCollection()
                ->map(fn (User $youth): array => ['name' => $youth->name, 'scans' => $youth->documentScans]);
            $matchingScanCount = DocumentScan::query()
                ->whereHas('user', fn (Builder $query): Builder => $query->where('role', Role::Youth->value))
                ->where('status', 'done')
                ->whereJsonLength('result->jobRecommendations', '>', 0)
                ->count();
        } else {
            $recommendationPages = DocumentScan::query()
                ->where('user_id', $user->getKey())
                ->where('status', 'done')
                ->whereJsonLength('result->jobRecommendations', '>', 0)
                ->latest('processed_at')
                ->paginate(12);

            $recommendationGroups = collect([['name' => $user->name, 'scans' => $recommendationPages->getCollection()]]);
            $matchingScanCount = $recommendationPages->total();
        }

        return view('peso.index', compact('recommendationPages', 'recommendationGroups', 'matchingScanCount', 'isPESOAdmin'));
    }
}
