<x-app-layout>
    <div class="suite-page trainer-courses">
        <x-workspace-heading eyebrow="ACCOUNT · SECURITY" title="Reset your password" description="Your administrator requested this reset. Choose a new password to keep your account secure." />
        <form method="POST" action="{{ route('account.password-reset.update') }}" class="trainer-courses__form">
            @csrf @method('PUT')
            <label>New password<input type="password" name="password" required autocomplete="new-password">@error('password')<small>{{ $message }}</small>@enderror</label>
            <label>Confirm new password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <div class="trainer-courses__form-actions"><button class="trainer-courses__primary" type="submit">Update password</button><a href="{{ route('notifications.index') }}">Cancel</a></div>
        </form>
    </div>
</x-app-layout>
