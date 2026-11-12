<x-app-layout>
    @php($editing = $account->exists)
    <div class="suite-page trainer-courses">
        <x-workspace-heading eyebrow="COMMUNITY · PEOPLE" :title="$editing ? 'Edit user account' : 'Add a user'" description="Admin-created accounts are verified immediately. Share the sign-in password securely with the user." />
        <form method="POST" action="{{ $editing ? route('admin.users.update', $account) : route('admin.users.store') }}" class="trainer-courses__form">
            @csrf
            @if ($editing)@method('PUT')@endif
            @if ($errors->any())<p class="trainer-courses__error" role="alert">Please review the highlighted fields.</p>@endif
            <div class="trainer-courses__form-row">
                <label>First name<input name="first_name" required maxlength="127" value="{{ old('first_name', $account->first_name ?? explode(' ', $account->name ?? '')[0]) }}">@error('first_name')<small>{{ $message }}</small>@enderror</label>
                <label>Last name<input name="last_name" required maxlength="127" value="{{ old('last_name', $account->last_name ?? trim(substr($account->name ?? '', strlen(explode(' ', $account->name ?? '')[0])))) }}">@error('last_name')<small>{{ $message }}</small>@enderror</label>
            </div>
            <label>Email<input name="email" type="email" required maxlength="255" value="{{ old('email', $account->email) }}">@error('email')<small>{{ $message }}</small>@enderror</label>
            <div class="trainer-courses__form-row">
                <label>Role<select name="role" required>@foreach (\App\Enums\Role::cases() as $role)<option value="{{ $role->value }}" @selected(old('role', $account->role?->value ?? 'youth') === $role->value)>{{ $role->label() }}</option>@endforeach</select>@error('role')<small>{{ $message }}</small>@enderror</label>
                <label>Training center (optional for TESDA accounts)<select name="training_center_id"><option value="">All centers</option>@foreach ($centers as $center)<option value="{{ $center->id }}" @selected((string) old('training_center_id', $account->training_center_id) === (string) $center->id)>{{ $center->name }} ({{ $center->type }})</option>@endforeach</select>@error('training_center_id')<small>{{ $message }}</small>@enderror</label>
                <label>Account status<select name="is_active"><option value="1" @selected(old('is_active', $account->exists ? (int) $account->is_active : 1) == 1)>Active</option><option value="0" @selected(old('is_active', $account->exists ? (int) $account->is_active : 1) == 0)>Inactive</option></select>@error('is_active')<small>{{ $message }}</small>@enderror</label>
            </div>
            <fieldset class="users-form__fieldset"><legend>Career Seeker details, when applicable</legend>
                <div class="trainer-courses__form-row"><label>Birth date<input name="date_of_birth" type="date" value="{{ old('date_of_birth', $account->date_of_birth?->toDateString()) }}">@error('date_of_birth')<small>{{ $message }}</small>@enderror</label><label>Phone<input name="contact_number" type="tel" maxlength="30" value="{{ old('contact_number', $account->youthProfile?->contact_number) }}">@error('contact_number')<small>{{ $message }}</small>@enderror</label></div>
                <label>Barangay<input name="barangay" maxlength="100" value="{{ old('barangay', $account->youthProfile?->barangay) }}">@error('barangay')<small>{{ $message }}</small>@enderror</label>
            </fieldset>
            @unless ($editing)
                <div class="trainer-courses__form-row"><label>Initial password<input name="password" type="password" required minlength="8" autocomplete="new-password">@error('password')<small>{{ $message }}</small>@enderror</label><label>Confirm password<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label></div>
            @endunless
            <div class="trainer-courses__form-actions"><button class="trainer-courses__primary" type="submit">{{ $editing ? 'Save changes' : 'Create account' }}</button><a href="{{ route('admin.users.index') }}">Cancel</a></div>
        </form>
    </div>
</x-app-layout>
