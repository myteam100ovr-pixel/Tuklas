<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use App\Services\CareerPathSkillMatcher;
use App\Support\CareerSkillCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CareerPathApiController extends Controller
{
    public function index(Request $request, CareerPathSkillMatcher $matcher): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ]);
        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? null;

        $careerPaths = CareerPath::query()
            ->when(! $user->hasRole('admin'), fn ($query) => $query->published())
            ->when($user->hasRole('admin') && in_array($status, ['draft', 'published', 'archived'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('education_level', 'like', '%'.$search.'%')))
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        $profile = $user->hasRole('youth') ? $user->youthProfile : null;
        $careerPaths->getCollection()->transform(function (CareerPath $careerPath) use ($matcher, $profile, $user): CareerPath {
            if ($user->hasRole('youth')) {
                $careerPath->setAttribute('skill_match', $matcher->match($careerPath, $profile));
            }

            return $careerPath;
        });

        return response()->json([
            'careers' => $careerPaths,
            'can_manage' => $user->hasRole('admin'),
            'career_skill_options' => $user->hasRole('admin') ? CareerSkillCatalog::OPTIONS : [],
            'assessment_skills_recorded' => $profile?->assessmentSkills() !== null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $careerPath = CareerPath::create($this->validatedData($request));

        return response()->json(['career' => $careerPath, 'message' => 'Career path saved.'], 201);
    }

    public function update(Request $request, CareerPath $careerPath): JsonResponse
    {
        $careerPath->update($this->validatedData($request));

        return response()->json(['career' => $careerPath->refresh(), 'message' => 'Career path updated.']);
    }

    public function destroy(CareerPath $careerPath): JsonResponse
    {
        $careerPath->delete();

        return response()->json(['message' => 'Career path deleted.']);
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $skillRules = $request->input('status') === 'published'
            ? ['required', 'array', 'min:3', 'max:8']
            : ['sometimes', 'nullable', 'array', 'max:8'];

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'education_level' => ['nullable', 'string', 'max:60'],
            'required_skills' => $skillRules,
            'required_skills.*' => ['string', 'distinct', Rule::in(CareerSkillCatalog::OPTIONS)],
            'source_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }
}
