<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Services\TesdaRegisteredProgramCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileCatalogController extends Controller
{
    public function index(Request $request, TesdaRegisteredProgramCatalog $registeredCatalog): JsonResponse
    {
        $careerSeekerCatalog = $request->boolean('career_seeker');
        $catalogUser = $request->user() ?? Auth::guard('sanctum')->user();
        $programQuery = TrainingProgram::published()
            ->with($careerSeekerCatalog ? 'center:id,name,type,address,is_active,is_demo' : 'center:id,name');

        if ($careerSeekerCatalog) {
            $programQuery
                ->where('is_demo', false)
                ->with(['sessions' => fn ($sessions) => $sessions
                    ->where('status', 'open')
                    ->where('starts_at', '>', now())
                    ->where('is_demo', false)
                    ->withCount(['applications as accepted_count' => fn ($applications) => $applications->where('status', 'accepted')])
                    ->with(['applications' => fn ($applications) => $applications
                        ->where('user_id', $catalogUser?->id ?? 0)
                        ->latest('id')])])
                ->whereHas('center', fn ($query) => $query
                    ->whereIn('type', ['PTC', 'PSAT'])
                    ->where('is_active', true)
                    ->where('is_demo', false));
        }

        $programs = $programQuery
            ->orderBy('title')
            ->paginate($careerSeekerCatalog ? 100 : 12);

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
                ...($careerSeekerCatalog ? ['training_center_type' => $program->center?->type] : []),
                ...($careerSeekerCatalog ? ['training_center_address' => $program->center?->address] : []),
                'requirements' => $program->requirements,
                'schedule_note' => $program->schedule_note,
                'last_verified_at' => $program->last_verified_at?->toDateString(),
                'is_demo' => $program->is_demo,
                ...($careerSeekerCatalog ? ['sessions' => $program->sessions->map(fn ($session): array => [
                    'id' => $session->id,
                    'starts_at' => $session->starts_at?->toIso8601String(),
                    'ends_at' => $session->ends_at?->toIso8601String(),
                    'capacity' => $session->capacity,
                    'accepted_count' => $session->accepted_count,
                    'available_places' => max(0, $session->capacity - $session->accepted_count),
                    'application_status' => $session->applications->first()?->status,
                ])->values()->all()] : []),
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
