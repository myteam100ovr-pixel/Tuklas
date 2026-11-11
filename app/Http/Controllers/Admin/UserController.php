<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TrainingCenter;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $users = User::query()
            ->when($request->query('status') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->select(['id', 'name', 'email', 'role', 'is_active', 'email_verified_at', 'created_at', 'deleted_at'])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);

        $data = ['users' => $users];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('admin.users.index', $data);
    }

    public function create(): View
    {
        return view('admin.users.form', ['account' => new User, 'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = (bool) $data['is_active'];

        $account = DB::transaction(function () use ($data): User {
            $account = User::forceCreate([
                'name' => trim($data['first_name']).' '.trim($data['last_name']),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'training_center_id' => in_array($data['role'], [Role::Trainer->value, Role::CenterRepresentative->value], true) ? ($data['training_center_id'] ?? null) : null,
                'is_active' => $data['is_active'],
                'email_verified_at' => now(),
                'date_of_birth' => $data['role'] === Role::Youth->value ? ($data['date_of_birth'] ?? null) : null,
            ]);

            if ($data['role'] === Role::Youth->value) {
                $account->youthProfile()->create([
                    'contact_number' => $data['contact_number'] ?? null,
                    'barangay' => $data['barangay'] ?? null,
                ]);
            }

            return $account;
        });

        $account->notify(new WorkspaceNotice('Account created', 'An administrator created your Tuklas account. You can sign in using the password they provided.'));
        $activity->record($request->user(), 'created '.$account->role->label().' account '.$account->name.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Account created and email verification completed.']);
        }

        return redirect()->route('admin.users.index')->with('status', 'Account created and email verification completed.');
    }

    public function edit(Request $request, User $user): View|JsonResponse
    {
        $data = ['account' => $user->loadMissing('youthProfile'), 'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get()];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('admin.users.form', $data);
    }

    public function update(Request $request, User $user, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request, $user);
        $data['is_active'] = (bool) $data['is_active'];
        $this->protectLastAdministrator($user, $data['role'], $data['is_active']);
        abort_if($request->user()->is($user) && $data['is_active'] === false, 422, 'You cannot deactivate your own account.');
        abort_if($request->user()->is($user) && $data['role'] !== Role::Admin->value, 422, 'You cannot change your own administrator role.');

        DB::transaction(function () use ($user, $data): void {
            $emailChanged = $user->email !== $data['email'];
            $user->forceFill([
                'name' => trim($data['first_name']).' '.trim($data['last_name']),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $data['email'],
                'role' => $data['role'],
                'training_center_id' => in_array($data['role'], [Role::Trainer->value, Role::CenterRepresentative->value], true) ? ($data['training_center_id'] ?? null) : null,
                'is_active' => $data['is_active'],
                'date_of_birth' => $data['role'] === Role::Youth->value ? ($data['date_of_birth'] ?? null) : null,
                'email_verified_at' => $emailChanged ? now() : $user->email_verified_at,
            ])->save();

            if ($data['role'] === Role::Youth->value) {
                $user->youthProfile()->updateOrCreate([], [
                    'contact_number' => $data['contact_number'] ?? null,
                    'barangay' => $data['barangay'] ?? null,
                ]);
            }
        });

        $user->notify(new WorkspaceNotice('Account updated', 'An administrator updated your Tuklas account details.'));
        $activity->record($request->user(), 'updated account '.$user->name.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Account updated.']);
        }

        return redirect()->route('admin.users.index')->with('status', 'Account updated.');
    }

    public function destroy(Request $request, User $user, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot delete your own account.');
        $this->protectLastAdministrator($user, null, false);
        $name = $user->name;
        $user->delete();
        $activity->record($request->user(), 'deleted account '.$name.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Account deleted. It can be restored from Deleted accounts.']);
        }

        return redirect()->route('admin.users.index')->with('status', 'Account deleted. It can be restored from Deleted accounts.');
    }

    public function restore(Request $request, int $user, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $account = User::onlyTrashed()->findOrFail($user);
        $account->restore();
        $activity->record($request->user(), 'restored account '.$account->name.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Account restored.']);
        }

        return redirect()->route('admin.users.index', ['status' => 'deleted'])->with('status', 'Account restored.');
    }

    public function resetPassword(Request $request, User $user, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $user->forceFill(['admin_password_reset_requested_at' => now()])->save();
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (Throwable $exception) {
            report($exception);
            $status = null;
        }

        $user->notify(new WorkspaceNotice('Password reset requested', 'An administrator requested a password reset for your account. Use the reset action here within 60 minutes, or check your email for a reset link.', 'account', null, route('account.password-reset.edit')));
        $activity->record($request->user(), 'requested a password reset for '.$user->name.'.');

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => $status === Password::RESET_LINK_SENT
            ? 'Reset action added to the user’s notifications and an email link sent.'
            : 'Reset action added to the user’s notifications. The email link could not be sent.']);
        }

        return back()->with('status', $status === Password::RESET_LINK_SENT
            ? 'Reset action added to the user’s notifications and an email link sent.'
            : 'Reset action added to the user’s notifications. The email link could not be sent.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, ?User $account = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($account?->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'training_center_id' => ['nullable', 'required_if:role,center_representative', Rule::exists('training_centers', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
            'password' => $account ? ['prohibited'] : ['required', 'string', 'min:8', 'confirmed'],
            'date_of_birth' => ['nullable', 'date'],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'barangay' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function protectLastAdministrator(User $user, ?string $newRole, bool $isActive): void
    {
        if ($user->role === Role::Admin && $user->is_active && ($newRole !== Role::Admin->value || ! $isActive)) {
            abort_if(User::query()->where('role', Role::Admin->value)->where('is_active', true)->count() <= 1, 422, 'The last active administrator cannot be removed.');
        }
    }
}
