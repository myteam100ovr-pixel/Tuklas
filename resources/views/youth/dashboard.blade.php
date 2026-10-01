<x-app-layout>
    <x-role-dashboard accent="youth" role-label="Youth workspace" eyebrow="Your next step / Youth" title="Your future has more than one path." description="Explore skills and opportunities at your pace. Your workspace will grow with you." :focus-areas="['Training', 'Careers', 'Opportunities']">
        <section class="role-dashboard__summary" aria-label="Workspace summary">
            <div class="role-dashboard__summary-card"><span>Account</span><strong>Active</strong></div>
            <div class="role-dashboard__summary-card"><span>Training catalog</span><strong>Coming soon</strong></div>
            <div class="role-dashboard__summary-card"><span>Career guidance</span><strong>Coming soon</strong></div>
            <div class="role-dashboard__summary-card"><span>Opportunities</span><strong>Coming soon</strong></div>
        </section>
        <div class="role-dashboard__workspace-grid">
            <section class="role-dashboard__panel" aria-labelledby="youth-pathway-title">
                <header class="role-dashboard__panel-heading"><div><h2 id="youth-pathway-title">Your career pathway</h2><p>Skills, guidance, and local opportunities</p></div><span class="role-dashboard__plan-label">Coming in stages</span></header>
                <div class="role-dashboard__empty-state"><span class="role-dashboard__empty-icon" aria-hidden="true">01</span><h3>Your path is ready to take shape</h3><p>Verified local career guidance will appear here when it is published.</p></div>
            </section>
            <div class="role-dashboard__sidebar">
                <section class="role-dashboard__panel" aria-labelledby="youth-actions-title">
                    <header class="role-dashboard__panel-heading"><div><h2 id="youth-actions-title">Your next steps</h2><p>Start with what you can do today</p></div></header>
                    <div class="role-dashboard__actions">
                        <a class="role-dashboard__action" href="{{ route('profile.show') }}"><span class="role-dashboard__action-mark">↗</span><span class="role-dashboard__action-copy"><strong>Update your profile</strong><small>Keep your account information current.</small></span></a>
                        <div class="role-dashboard__action is-planned"><span class="role-dashboard__action-mark">02</span><span class="role-dashboard__action-copy"><strong>Explore skills training</strong><small>Verified programs will appear when published.</small></span></div>
                        <div class="role-dashboard__action is-planned"><span class="role-dashboard__action-mark">03</span><span class="role-dashboard__action-copy"><strong>Discover career paths</strong><small>Career guidance is coming later.</small></span></div>
                    </div>
                </section>
                <x-career-chat />
            </div>
        </div>
        <section class="role-dashboard__panel role-dashboard__lower" aria-labelledby="youth-programs-title">
            <header class="role-dashboard__panel-heading"><div><h2 id="youth-programs-title">Training near you</h2><p>Verified programs published for local youth</p></div><span class="role-dashboard__plan-label">No published programs</span></header>
            <div class="role-dashboard__empty-state"><h3>No verified programs to show yet</h3><p>Programs will appear here after they are reviewed and published.</p></div>
        </section>
    </x-role-dashboard>
</x-app-layout>
