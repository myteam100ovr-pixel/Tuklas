<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DocumentScan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePesoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $scans = DocumentScan::query()
            ->where('status', 'done')
            ->whereJsonLength('result->jobRecommendations', '>', 0);

        if ($user->hasRole(Role::SuperAdmin)) {
            $scans
                ->with(['user:id,name'])
                ->whereHas('user', fn (Builder $query): Builder => $query->where('role', Role::Youth->value));
        } else {
            $scans->where('user_id', $user->getKey());
        }

        $matches = $scans
            ->latest('processed_at')
            ->limit(100)
            ->get()
            ->map(fn (DocumentScan $scan): array => [
                'id' => $scan->getKey(),
                'name' => $user->hasRole(Role::SuperAdmin) ? $scan->user?->name : null,
                'document_type' => $scan->doc_type,
                'original_name' => $scan->original_name,
                'processed_at' => $scan->processed_at,
                'job_recommendations' => data_get($scan->result, 'jobRecommendations', []),
                'skill_gaps' => data_get($scan->result, 'skillGaps', []),
                'learning_recommendations' => data_get($scan->result, 'learningRecommendations', []),
            ])
            ->values();

        return response()->json(['matches' => $matches]);
    }
}
