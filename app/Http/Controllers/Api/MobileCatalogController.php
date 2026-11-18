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
        $careerSeekerCatalog = $request->boolean('career_seeker');
        $programQuery = TrainingProgram::published()
            ->with($careerSeekerCatalog ? 'center:id,name,type,is_active,is_demo' : 'center:id,name');

        if ($careerSeekerCatalog) {
            $programQuery
                ->where('is_demo', false)
                ->whereHas('center', fn ($query) => $query
                    ->whereIn('type', ['PTC', 'PSAT'])
                    ->where('is_active', true)
                    ->where('is_demo', false));
        }

        $programs = $programQuery->orderBy('title')->paginate(12);

        $registeredDirectory = $registeredCatalog->all();
        $providerDirectory = $careerSeekerCatalog ? $registeredCatalog->otherRegisteredProviders() : $registeredDirectory;
        $registeredPage = max(1, $request->integer('registered_page', 1));
        $registeredPerPage = min(100, max(1, $request->integer('registered_per_page', 25)));
        $registeredRecords = collect($providerDirectory['records']);

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
                'is_demo' => $program->is_demo,
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
                'source' => $providerDirectory['source'],
                'fetched_at' => $providerDirectory['fetched_at'],
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
            'notice' => $careerSeekerCatalog
                ? 'PTC and PSAT listings do not confirm current enrollment or open seats. Other providers are listed in the TESDA registry; contact each school to confirm schedules, duration, slots, fees, and eligibility.'
                : 'Confirm schedules, slots, fees, and eligibility with TESDA or the training provider.',
        ]);
    }
}
