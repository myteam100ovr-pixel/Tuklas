<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\TrainerQualification;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\StarterCareerRecommendations;
use App\Services\TesdaQualificationCatalog;
use App\Services\TesdaRegisteredProgramCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TesdaCatalogController extends Controller
{
    public function index(Request $request, TesdaQualificationCatalog $catalog, TesdaRegisteredProgramCatalog $registeredCatalog, StarterCareerRecommendations $starterRecommendations): View|JsonResponse
    {
        $search = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $centerType = $request->user()?->hasRole(Role::Youth)
            ? ($request->validate(['center_type' => ['nullable', Rule::in(['PTC', 'PSAT', 'PRIVATE'])]])['center_type'] ?? null)
            : null;
        $choiceUser = null;
        if ($request->user()?->hasRole(Role::Youth)) {
            $choiceUser = $request->user();
        } elseif ($request->user()?->hasRole(Role::TesdaAdmin, Role::Admin) && $request->filled('for_user')) {
            $choiceUser = User::query()
                ->whereKey($request->integer('for_user'))
                ->where('role', Role::Youth->value)
                ->where('is_active', true)
                ->firstOrFail();
        }
        $choiceUser?->load('tesdaChoice.selector');

        $programs = TrainingProgram::published()
            ->when($search !== '', fn ($query) => $query->where(fn ($matches) => $matches
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('nc_level', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')))
            ->when($request->user()?->hasRole(Role::Youth), fn ($query) => $query
                ->where('is_demo', false)
                ->whereHas('center', fn ($center) => $center
                    ->whereIn('type', ['PTC', 'PSAT', 'PRIVATE'])
                    ->where('is_active', true)
                    ->where('is_demo', false)
                    ->when($centerType !== null, fn ($center) => $center->where('type', $centerType))))
            ->when($request->user()?->isCenterScopedTesda(), fn ($query) => $query->where('training_center_id', $request->user()->training_center_id))
            ->with(['teachingAssignments.trainer:id,name', 'center:id,name,type,is_active,is_demo,address'])
            ->when($request->user()?->hasRole(Role::Youth), fn ($query) => $query->with([
                'sessions' => fn ($sessions) => $sessions
                    ->where('status', 'open')
                    ->where('starts_at', '>', now())
                    ->where('is_demo', false)
                    ->withCount(['applications as accepted_count' => fn ($applications) => $applications->where('status', 'accepted')])
                    ->with([
                        'applications' => fn ($applications) => $applications
                            ->where('user_id', $request->user()->id)
                            ->latest('id'),
                    ]),
                'applications' => fn ($applications) => $applications
                    ->where('user_id', $request->user()->id)
                    ->latest('id'),
            ]))
            ->orderBy('title')
            ->get(['id', 'title', 'nc_level', 'description', 'duration_hours', 'duration_days', 'training_center_id', 'schedule_note', 'last_verified_at', 'is_demo']);
        $levelOrder = ['NC I' => 0, 'NC II' => 1, 'NC III' => 2, 'NC IV' => 3, 'Other / Not specified' => 4];
        $programGroups = $programs
            ->groupBy(fn (TrainingProgram $program): string => filled($program->nc_level) ? trim($program->nc_level) : 'Other / Not specified')
            ->sortBy(fn ($levelPrograms, string $level): int => $levelOrder[$level] ?? 5);

        $teachingByProgram = $request->user()?->hasRole(Role::TesdaAdmin)
            ? $request->user()->teachingAssignments()
                ->whereIn('training_program_id', $programs->pluck('id'))
                ->get(['training_program_id'])
                ->keyBy('training_program_id')
            : collect();
        $trainerQualifications = $request->user()?->hasRole(Role::TesdaAdmin)
            ? $request->user()->trainerQualifications()->get(['qualification_id', 'title', 'nc_level'])->keyBy('qualification_id')
            : collect();

        $nationalCatalog = $catalog->all();
        $registeredDirectory = $registeredCatalog->all();
        $outsideDirectory = $registeredCatalog->outsidePangasinan();
        $nationalCatalog['qualifications'] = $registeredCatalog->addPangasinanLocations(
            $nationalCatalog['qualifications'],
            $registeredDirectory['records'],
            $outsideDirectory['records'],
        );
        $providerGroups = collect($registeredDirectory['records'])
            ->groupBy(fn (array $record): string => mb_strtolower($record['provider'].'|'.$record['address']))
            ->map(fn ($records): array => [
                'provider' => $records->first()['provider'],
                'address' => $records->first()['address'] ?: 'Not available',
                'phones' => $records->pluck('phone')->filter()->unique()->values(),
                'programs' => $records->pluck('program')->filter()->unique()->sort()->values(),
            ])
            ->sortBy(fn (array $provider): string => $provider['provider'])
            ->values();

        $careerSeekerPrograms = collect();
        $careerSeekerProgramGroups = collect();
        $careerSeekerProviders = collect();
        $careerSeekerTrainingRecommendations = [];
        if ($request->user()?->hasRole(Role::Youth)) {
            $careerSeekerTrainingRecommendations = $starterRecommendations->for($request->user())['training'];
            $careerSeekerPrograms = $programs
                ->filter(fn (TrainingProgram $program): bool => $program->center !== null)
                ->values();
            $careerSeekerProgramGroups = $careerSeekerPrograms
                ->groupBy(fn (TrainingProgram $program): int => $program->center->id)
                ->sortBy(fn ($schoolPrograms): array => [
                    ['PTC' => 0, 'PSAT' => 1, 'PRIVATE' => 2][$schoolPrograms->first()->center->type] ?? 3,
                    mb_strtolower($schoolPrograms->first()->center->name),
                ]);

            if ($centerType === null || $centerType === 'PRIVATE') {
                $otherRegisteredDirectory = $registeredCatalog->otherRegisteredProviders();
                $careerSeekerProviders = collect($otherRegisteredDirectory['records'])
                    ->groupBy(fn (array $record): string => mb_strtolower($record['provider'].'|'.$record['address']))
                    ->map(fn ($records): array => [
                        'provider' => $records->first()['provider'],
                        'address' => $records->first()['address'] ?: 'Not available',
                        'phones' => $records->pluck('phone')->filter()->unique()->values(),
                        'programs' => $records->pluck('program')->filter()->unique()->sort()->values(),
                    ])
                    ->sortBy(fn (array $provider): array => [
                        str_contains(mb_strtolower($provider['provider']), 'edson') ? 0 : (str_contains(mb_strtolower($provider['provider']), 'ledesma') ? 1 : 2),
                        mb_strtolower($provider['provider']),
                    ])
                    ->values();
            }
        }

        $data = [
            'nationalCatalog' => $nationalCatalog,
            'providerGroups' => $providerGroups,
            'providerCount' => $providerGroups->count(),
            'careerSeekerPrograms' => $careerSeekerPrograms,
            'careerSeekerProgramGroups' => $careerSeekerProgramGroups,
            'careerSeekerCenterType' => $centerType,
            'careerSeekerProviders' => $careerSeekerProviders,
            'careerSeekerProviderCount' => $careerSeekerProviders->count(),
            'careerSeekerTrainingRecommendations' => $careerSeekerTrainingRecommendations,
            'registeredProgramCount' => count($registeredDirectory['records']),
            'registeredProgramsFetchedAt' => $registeredDirectory['fetched_at'],
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
