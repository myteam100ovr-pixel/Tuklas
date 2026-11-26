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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) ($request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ])['q'] ?? ''));

        $users = User::query()
            ->when($request->query('status') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->select(['id', 'name', 'email', 'role', 'is_active', 'email_verified_at', 'created_at', 'deleted_at'])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $data = ['users' => $users];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('admin.users.index', $data);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'account' => new User,
            'centers' => TrainingCenter::query()->where('is_active', true)->orderBy('name')->get(),
            'initialPassword' => $this->generateInitialPassword(),
        ]);
    }

    public function store(Request $request, ActivityNotifier $activity): RedirectResponse|JsonResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = (bool) $data['is_active'];

        $account = DB::transaction(function () use ($data): User {
            $account = User::forceCreate([
                'name' => $this->displayName($data),
                'first_name' => trim($data['first_name']),
                'middle_initial' => filled($data['middle_initial'] ?? null) ? trim($data['middle_initial']) : null,
                'last_name' => trim($data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'training_center_id' => $this->assignedTrainingCenterId($data),
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
                'name' => $this->displayName($data),
                'first_name' => trim($data['first_name']),
                'middle_initial' => filled($data['middle_initial'] ?? null) ? trim($data['middle_initial']) : null,
                'last_name' => trim($data['last_name']),
                'email' => $data['email'],
                'role' => $data['role'],
                'training_center_id' => $this->assignedTrainingCenterId($data),
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
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($account?->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'tesda_account_type' => ['sometimes', 'required', Rule::in(['trainer', 'center'])],
            'training_center_id' => [
                Rule::requiredIf(
                    $request->input('role') === Role::TesdaAdmin->value
                    && $request->input('tesda_account_type') === 'center',
                ),
                'nullable',
                Rule::exists('training_centers', 'id')->where('is_active', true),
            ],
            'is_active' => ['required', 'boolean'],
            'password' => $account ? ['prohibited'] : [
                'required',
                'string',
                PasswordRule::min(12)->max(72)->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
            'date_of_birth' => [
                'bail',
                Rule::requiredIf(
                    $request->input('role') === Role::Youth->value && (bool) $request->input('is_active'),
                ),
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (
                        $request->input('role') !== Role::Youth->value
                        || ! (bool) $request->input('is_active')
                        || ! is_string($value)
                    ) {
                        return;
                    }

                    $age = Carbon::parse($value)->age;

                    if ($age < 18 || $age > 30) {
                        $fail('Career Seeker accounts must be for people aged 18 to 30.');
                    }
                },
            ],
            'contact_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'barangay' => ['nullable', 'string', 'max:100'],
        ], [
            'password' => 'Use 12 to 72 characters, including uppercase and lowercase letters, a number, and a symbol.',
            'training_center_id.required' => 'Select a training center for a Center TESDA account.',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function assignedTrainingCenterId(array $data): ?int
    {
        if ($data['role'] !== Role::TesdaAdmin->value || ($data['tesda_account_type'] ?? null) === 'trainer') {
            return null;
        }

        return isset($data['training_center_id']) ? (int) $data['training_center_id'] : null;
    }

    private function generateInitialPassword(): string
    {
        do {
            $password = Str::password(24);
        } while (
            ! preg_match('/[a-z]/', $password)
            || ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[0-9]/', $password)
            || ! preg_match('/[^a-zA-Z0-9]/', $password)
        );

        return $password;
    }

    /** @param array<string, mixed> $data */
    private function displayName(array $data): string
    {
        return collect([
            trim($data['first_name']),
            trim((string) ($data['middle_initial'] ?? '')),
            trim($data['last_name']),
        ])->filter()->implode(' ');
    }

    private function protectLastAdministrator(User $user, ?string $newRole, bool $isActive): void
    {
        if ($user->role === Role::Admin && $user->is_active && ($newRole !== Role::Admin->value || ! $isActive)) {
            abort_if(User::query()->where('role', Role::Admin->value)->where('is_active', true)->count() <= 1, 422, 'The last active administrator cannot be removed.');
        }
    }
}
