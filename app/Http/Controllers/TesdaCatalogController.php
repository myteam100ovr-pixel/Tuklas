<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgram;
use Illuminate\View\View;

class TesdaCatalogController extends Controller
{
    public function index(): View
    {
        $programs = TrainingProgram::published()
            ->orderBy('title')
            ->get(['id', 'title', 'nc_level', 'description', 'duration_hours', 'schedule_note', 'last_verified_at']);
        $levelOrder = ['NC I' => 0, 'NC II' => 1, 'NC III' => 2, 'NC IV' => 3, 'Other / Not specified' => 4];
        $programGroups = $programs
            ->groupBy(fn (TrainingProgram $program): string => filled($program->nc_level) ? trim($program->nc_level) : 'Other / Not specified')
            ->sortBy(fn ($levelPrograms, string $level): int => $levelOrder[$level] ?? 5);

        return view('tesda.index', [
            'programCount' => $programs->count(),
            'programGroups' => $programGroups,
        ]);
    }
}
