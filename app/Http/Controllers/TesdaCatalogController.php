<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\TrainerQualification;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\TesdaQualificationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TesdaCatalogController extends Controller
{
    public function index(Request $request, TesdaQualificationCatalog $catalog): View|JsonResponse
    {
        $choiceUser = null;
        if ($request->user()?->hasRole(Role::Youth)) {
            $choiceUser = $request->user();
        } elseif ($request->user()?->hasRole(Role::Trainer, Role::SuperAdmin) && $request->filled('for_user')) {
            $choiceUser = User::query()
                ->whereKey($request->integer('for_user'))
                ->where('role', Role::Youth->value)
                ->where('is_active', true)
                ->firstOrFail();
        }
        $choiceUser?->load('tesdaChoice.selector');

        $programs = TrainingProgram::published()
            ->with('teachingAssignments.trainer:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'nc_level', 'description', 'duration_hours', 'schedule_note', 'last_verified_at']);
        $levelOrder = ['NC I' => 0, 'NC II' => 1, 'NC III' => 2, 'NC IV' => 3, 'Other / Not specified' => 4];
        $programGroups = $programs
            ->groupBy(fn (TrainingProgram $program): string => filled($program->nc_level) ? trim($program->nc_level) : 'Other / Not specified')
            ->sortBy(fn ($levelPrograms, string $level): int => $levelOrder[$level] ?? 5);

        $teachingByProgram = $request->user()?->hasRole(Role::Trainer)
            ? $request->user()->teachingAssignments()
                ->whereIn('training_program_id', $programs->pluck('id'))
                ->get(['training_program_id'])
                ->keyBy('training_program_id')
            : collect();
        $trainerQualifications = $request->user()?->hasRole(Role::Trainer)
            ? $request->user()->trainerQualifications()->get(['qualification_id', 'title', 'nc_level'])->keyBy('qualification_id')
            : collect();

        $data = [
            'nationalCatalog' => $catalog->all(),
            'programCount' => $programs->count(),
            'programGroups' => $programGroups,
            'choiceUser' => $choiceUser,
            'teachingByProgram' => $teachingByProgram,
            'trainerQualifications' => $trainerQualifications,
            'qualificationTrainers' => TrainerQualification::query()
                ->with('trainer:id,name')
                ->get(['id', 'qualification_id', 'user_id'])
                ->groupBy('qualification_id'),
        ];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('tesda.index', $data);
    }
}
