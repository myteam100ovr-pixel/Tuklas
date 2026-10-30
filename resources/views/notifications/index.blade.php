<x-app-layout>
    <div class="suite-page trainer-courses">
        <x-workspace-heading eyebrow="WORKSPACE · UPDATES" title="Notifications" :description="auth()->user()->hasRole(\App\Enums\Role::SuperAdmin) ? 'Activity across Youth, TESDA trainers, and administrators.' : 'Updates about your Tuklas account.'" />
        @if (session('status'))<p class="trainer-courses__feedback" role="status">{{ session('status') }}</p>@endif
        <div class="users-directory__toolbar"><span>{{ auth()->user()->unreadNotifications()->count() }} unread</span><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button type="submit">Mark all as read</button></form></div>
        <section aria-label="Notifications" class="notifications-list">
            @forelse ($notifications as $notice)
                <article class="notifications-list__item {{ $notice->read_at ? '' : 'is-unread' }}">
                    <div><span class="trainer-courses__status">{{ $notice->data['title'] ?? 'Update' }}</span><p>{{ $notice->data['message'] ?? '' }}</p><small>{{ $notice->created_at->diffForHumans() }}</small>@if (($notice->data['action_url'] ?? null) && auth()->user()->admin_password_reset_requested_at?->copy()->addHour()->isFuture())<p><a href="{{ $notice->data['action_url'] }}">Reset my password ↗</a></p>@endif</div>
                    @unless ($notice->read_at)<form method="POST" action="{{ route('notifications.read', $notice->id) }}">@csrf<button type="submit">Mark read</button></form>@endunless
                </article>
            @empty
                <div class="trainer-courses__empty">No notifications yet.</div>
            @endforelse
            {{ $notifications->links() }}
        </section>
    </div>
</x-app-layout>
