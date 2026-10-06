<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerRecommendationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $isPESOAdmin = $user->hasRole(Role::SuperAdmin);
        $scans = DocumentScan::query()
            ->where('status', 'done')
            ->whereJsonLength('result->jobRecommendations', '>', 0);

        if ($isPESOAdmin) {
            $scans
                ->with(['user:id,name'])
                ->whereHas('user', fn (Builder $query): Builder => $query->where('role', Role::Youth->value));
        } else {
            $scans->where('user_id', $user->getKey());
        }

        $recommendationScans = $scans->latest('processed_at')->paginate(12);

        return view('peso.index', compact('recommendationScans', 'isPESOAdmin'));
    }
}
