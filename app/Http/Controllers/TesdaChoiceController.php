<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\TesdaQualificationCatalog;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TesdaChoiceController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Trainer, Role::TesdaAdmin, Role::Admin), 403);

        $data = [
            'youthUsers' => User::query()
                ->where('role', Role::Youth->value)
                ->where('is_active', true)
                ->with('tesdaChoice')
                ->orderBy('name')
                ->paginate(20),
        ];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('tesda.choose-youth', $data);
    }

    public function store(Request $request, TesdaQualificationCatalog $catalog, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'qualification_id' => ['required', 'string', 'max:30'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $actor = $request->user();
        if (isset($validated['user_id'])) {
            abort_unless($actor->hasRole(Role::Trainer, Role::TesdaAdmin, Role::Admin), 403);
            $target = User::query()
                ->whereKey($validated['user_id'])
                ->where('role', Role::Youth->value)
                ->where('is_active', true)
                ->firstOrFail();
        } else {
            abort_unless($actor->hasRole(Role::Youth), 403);
            $target = $actor;
        }

        $qualification = collect($catalog->all()['qualifications'])
            ->first(fn (array $item): bool => $item['id'] === $validated['qualification_id'] && $item['status'] === 'current');
        if ($qualification === null) {
            if ($request->routeIs('mobile.*')) {
                throw ValidationException::withMessages(['qualification_id' => 'Choose a current TESDA qualification.']);
            }

            return back()->withErrors(['qualification_id' => 'Choose a current TESDA qualification.']);
        }

        $target->tesdaChoice()->updateOrCreate([], [
            'qualification_id' => $qualification['id'],
            'qualification_title' => $qualification['title'],
            'nc_level' => $qualification['nc_level'],
            'selected_by' => $actor->id,
        ]);
        $activity->record($actor, 'selected '.$qualification['title'].' as a training plan for '.$target->name.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'TESDA training plan saved. Confirm enrollment details with a registered provider.']);
        }

        return redirect()->to(route('tesda.index', isset($validated['user_id']) ? ['for_user' => $target->id] : []).'#nc-offerings')
            ->with('status', 'TESDA training plan saved. Confirm enrollment details with a registered provider.');
    }
}
