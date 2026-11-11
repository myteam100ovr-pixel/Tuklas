<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Services\TesdaRegisteredProgramCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileCatalogController extends Controller
{
    public function index(Request $request, TesdaRegisteredProgramCatalog $registeredCatalog): JsonResponse
    {
        $programs = TrainingProgram::published()
            ->with('center:id,name')
            ->orderBy('title')
            ->paginate(12);

        $registeredDirectory = $registeredCatalog->all();
        $registeredPage = max(1, $request->integer('registered_page', 1));
        $registeredPerPage = min(100, max(1, $request->integer('registered_per_page', 25)));
        $registeredRecords = collect($registeredDirectory['records']);

        return response()->json([
            'programs' => $programs->getCollection()->map(fn (TrainingProgram $program): array => [
                'id' => $program->id,
                'title' => $program->title,
                'nc_level' => $program->nc_level,
                'description' => $program->description,
                'duration_hours' => $program->duration_hours,
                'duration_days' => $program->duration_days,
                'training_center' => $program->center?->name,
                'requirements' => $program->requirements,
                'schedule_note' => $program->schedule_note,
                'last_verified_at' => $program->last_verified_at?->toDateString(),
            ])->all(),
            'pagination' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'total' => $programs->total(),
            ],
            'registered_programs' => $registeredRecords->forPage($registeredPage, $registeredPerPage)->values()->all(),
            'registered_programs_pagination' => [
                'current_page' => $registeredPage,
                'last_page' => (int) ceil($registeredRecords->count() / $registeredPerPage),
                'per_page' => $registeredPerPage,
                'total' => $registeredRecords->count(),
                'source' => $registeredDirectory['source'],
                'fetched_at' => $registeredDirectory['fetched_at'],
            ],
            'resources' => [
                ...collect(['NC I', 'NC II', 'NC III', 'NC IV'])
                    ->map(fn (string $level): array => [
                        'title' => $level.' registered programs',
                        'url' => 'https://www.tesda.gov.ph/Tvi/Result?SearchCourse='.urlencode($level).'&SearchLoc=pangasinan',
                    ])
                    ->all(),
                ['title' => 'Pangasinan training centers and all registered programs', 'url' => 'https://www.tesda.gov.ph/Tvi/Result?SearchCourse=&SearchIns=&SearchLoc=pangasinan'],
                ['title' => 'TESDA Online Program', 'url' => 'https://e-tesda.gov.ph/course/'],
                ['title' => 'Scholarships and assistance', 'url' => 'https://tesda.gov.ph/About/TESDA/1279'],
                ['title' => 'Assessment and certification', 'url' => 'https://tesda.gov.ph/about/tesda/25'],
                ['title' => 'Training regulations', 'url' => 'https://tesda.gov.ph/Download/Training_Regulations'],
            ],
            'notice' => 'Confirm schedules, slots, fees, and eligibility with TESDA or the training provider.',
        ]);
    }
}
