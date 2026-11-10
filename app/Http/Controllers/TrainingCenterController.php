<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\TrainingCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingCenterController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $canManage = $request->user()->hasRole(Role::SuperAdmin, Role::TesdaAdmin);
        $centers = TrainingCenter::query()
            ->when(! $canManage, fn ($query) => $query->where('is_active', true))
            ->when($request->user()->hasRole(Role::CenterRepresentative), fn ($query) => $query->whereKey($request->user()->training_center_id))
            ->orderBy('name')->paginate(20);

        return $request->routeIs('mobile.*') ? response()->json(['centers' => $centers, 'can_manage' => $canManage]) : view('operations.centers', compact('centers', 'canManage'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request);
        TrainingCenter::create($this->validatedData($request));

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Training center saved.'], 201) : back()->with('status', 'Training center saved.');
    }

    public function update(Request $request, TrainingCenter $center): RedirectResponse|JsonResponse
    {
        $this->authorizeManager($request);
        $center->update($this->validatedData($request));

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Training center updated.']) : back()->with('status', 'Training center updated.');
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(Role::SuperAdmin, Role::TesdaAdmin), 403);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['PTC', 'PSAT'])],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
