<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CareerPathApiController extends Controller
{
    public function index(Request $request): JsonResponse
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

        return response()->json([
            'careers' => $careerPaths,
            'can_manage' => $user->hasRole('admin'),
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
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'education_level' => ['nullable', 'string', 'max:60'],
            'source_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }
}
