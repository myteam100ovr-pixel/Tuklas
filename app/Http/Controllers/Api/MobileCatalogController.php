<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;

class MobileCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $programs = TrainingProgram::published()
            ->orderBy('title')
            ->paginate(12);

        return response()->json([
            'programs' => $programs->getCollection()->map(fn (TrainingProgram $program): array => [
                'id' => $program->id,
                'title' => $program->title,
                'nc_level' => $program->nc_level,
                'description' => $program->description,
                'duration_hours' => $program->duration_hours,
                'requirements' => $program->requirements,
                'schedule_note' => $program->schedule_note,
                'last_verified_at' => $program->last_verified_at?->toDateString(),
            ])->all(),
            'pagination' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'total' => $programs->total(),
            ],
            'resources' => [
                ...collect(['NC I', 'NC II', 'NC III', 'NC IV'])
                    ->map(fn (string $level): array => [
                        'title' => $level.' registered programs',
                        'url' => 'https://www.tesda.gov.ph/Tvi/Result?SearchCourse='.urlencode($level).'&SearchLoc=pangasinan',
                    ])
                    ->all(),
                ['title' => 'TESDA Online Program', 'url' => 'https://e-tesda.gov.ph/course/'],
                ['title' => 'Scholarships and assistance', 'url' => 'https://tesda.gov.ph/About/TESDA/1279'],
                ['title' => 'Assessment and certification', 'url' => 'https://tesda.gov.ph/about/tesda/25'],
                ['title' => 'Training regulations', 'url' => 'https://tesda.gov.ph/Download/Training_Regulations'],
            ],
            'notice' => 'Confirm schedules, slots, fees, and eligibility with TESDA or the training provider.',
        ]);
    }
}
