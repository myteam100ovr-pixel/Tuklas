<x-app-layout>
    @php
        $editing = $account->exists;
        $selectedRole = old('role', $account->role?->value ?? 'youth');
        $selectedStatus = old('is_active', $account->exists ? (int) $account->is_active : 1);
        $selectedTesdaType = old('tesda_account_type', $account->training_center_id ? 'center' : 'trainer');
    @endphp
    <div class="suite-page trainer-courses">
        <x-workspace-heading eyebrow="COMMUNITY · PEOPLE" :title="$editing ? 'Edit user account' : 'Add a user'" :description="$editing ? 'Update this account’s details and access.' : 'A unique strong initial password is generated for each account. Share it with the user through a secure channel.'" />
        <form method="POST" action="{{ $editing ? route('admin.users.update', $account) : route('admin.users.store') }}" class="trainer-courses__form" data-tesda-account-form>
            @csrf
            @if ($editing)@method('PUT')@endif
            @if ($errors->any())<p class="trainer-courses__error" role="alert">{{ __('Please review the highlighted fields.') }}</p>@endif
            <div class="trainer-courses__form-row">
                <label>Last name<input name="last_name" required maxlength="127" value="{{ old('last_name', $account->last_name ?? trim(substr($account->name ?? '', strlen(explode(' ', $account->name ?? '')[0])))) }}">@error('last_name')<small>{{ $message }}</small>@enderror</label>
                <label>First name<input name="first_name" required maxlength="127" value="{{ old('first_name', $account->first_name ?? explode(' ', $account->name ?? '')[0]) }}">@error('first_name')<small>{{ $message }}</small>@enderror</label>
                <label>Middle initial<input name="middle_initial" maxlength="10" value="{{ old('middle_initial', $account->middle_initial) }}">@error('middle_initial')<small>{{ $message }}</small>@enderror</label>
            </div>
            <label>{{ __('Email') }}<input name="email" type="email" required maxlength="255" value="{{ old('email', $account->email) }}">@error('email')<small>{{ $message }}</small>@enderror</label>
            <div class="trainer-courses__form-row">
                <label>{{ __('Role') }}<select name="role" required data-user-role>@foreach (\App\Enums\Role::cases() as $role)<option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->label() }}</option>@endforeach</select>@error('role')<small>{{ $message }}</small>@enderror</label>
                <label data-tesda-account-type-field hidden>TESDA account type<select name="tesda_account_type" required disabled data-tesda-account-type><option value="trainer" @selected($selectedTesdaType === 'trainer')>Trainer</option><option value="center" @selected($selectedTesdaType === 'center')>Center</option></select>@error('tesda_account_type')<small>{{ $message }}</small>@enderror</label>
                <label data-tesda-center-field hidden>Training center<select name="training_center_id" disabled data-tesda-center><option value="">Select a training center</option>@foreach ($centers as $center)<option value="{{ $center->id }}" @selected((string) old('training_center_id', $account->training_center_id) === (string) $center->id)>{{ $center->name }} ({{ $center->type }})</option>@endforeach</select>@error('training_center_id')<small>{{ $message }}</small>@enderror</label>
                <label>Account status<select name="is_active"><option value="1" @selected($selectedStatus == 1)>{{ __('Active') }}</option><option value="0" @selected($selectedStatus == 0)>{{ __('Inactive') }}</option></select>@error('is_active')<small>{{ $message }}</small>@enderror</label>
            </div>
            <fieldset class="users-form__fieldset"><legend>Career Seeker details, when applicable</legend>
                <div class="trainer-courses__form-row"><label>Birth date<input name="date_of_birth" type="date" min="{{ now()->subYears(31)->addDay()->toDateString() }}" max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('date_of_birth', $account->date_of_birth?->toDateString()) }}" @if ($selectedRole === \App\Enums\Role::Youth->value && $selectedStatus) required @endif>@error('date_of_birth')<small>{{ $message }}</small>@enderror<small>Required for active Career Seeker accounts, ages 18–30.</small></label><label>{{ __('Phone') }}<input name="contact_number" type="tel" maxlength="30" value="{{ old('contact_number', $account->youthProfile?->contact_number) }}">@error('contact_number')<small>{{ $message }}</small>@enderror</label></div>
                <label>{{ __('Barangay') }}<input name="barangay" maxlength="100" value="{{ old('barangay', $account->youthProfile?->barangay) }}">@error('barangay')<small>{{ $message }}</small>@enderror</label>
            </fieldset>
            @unless ($editing)
                <div class="users-form__password" data-password-strength-form>
                    <div class="trainer-courses__form-row">
                        <label for="user-initial-password">Initial password
                            <span class="users-form__password-input"><input id="user-initial-password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" value="{{ $initialPassword }}" data-password-strength-input><button type="button" class="users-form__password-toggle" aria-controls="user-initial-password user-password-confirmation" aria-label="{{ __('Show password') }}" aria-pressed="false" title="{{ __('Show password') }}" data-password-visibility><svg data-password-eye-open viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg><svg data-password-eye-closed viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 6.3A10.8 10.8 0 0 1 12 6c6.2 0 9.5 6 9.5 6a15.6 15.6 0 0 1-3.1 3.8M6.1 6.2C3.8 7.8 2.5 12 2.5 12s3.3 6 9.5 6c1.2 0 2.3-.2 3.3-.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg></button></span>
                            @error('password')<small>{{ $message }}</small>@enderror
                        </label>
                        <label for="user-password-confirmation">{{ __('Confirm password') }}<input id="user-password-confirmation" name="password_confirmation" type="password" required minlength="12" maxlength="72" autocomplete="new-password" value="{{ $initialPassword }}" data-password-confirmation><small class="users-form__confirmation-error" data-password-confirmation-message hidden>{{ __('Passwords do not match.') }}</small></label>
                    </div>
                    <div class="users-form__strength" data-password-strength-meter data-strength="secure" aria-live="polite">
                        <div class="users-form__strength-heading"><span>{{ __('Password strength') }}</span><strong data-password-strength-label>Secure</strong></div>
                        <div class="users-form__strength-track" aria-hidden="true"><span data-password-strength-bar></span></div>
                        <p data-password-strength-message>Strong enough to protect this account. You can change it before creating the account.</p>
                        <small>{{ __('Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.') }}</small>
                    </div>
                </div>
            @endunless
            <div class="trainer-courses__form-actions"><button class="trainer-courses__primary" type="submit" @unless ($editing) data-password-submit @endunless>{{ $editing ? 'Save changes' : 'Create account' }}</button><a href="{{ route('admin.users.index') }}">{{ __('Cancel') }}</a></div>
        </form>
    </div>
</x-app-layout>
