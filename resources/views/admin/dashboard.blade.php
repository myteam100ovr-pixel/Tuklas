<x-app-layout>
    <x-role-dashboard accent="admin" role-label="PESO Bugallon · Super Admin" eyebrow="Control room / Local workforce" title="Good morning, {{ auth()->user()->name }}." description="A central workspace for guiding Bugallon’s youth, training, and employment programs." :focus-areas="['People', 'Programs', 'Reports']">
        <section class="role-dashboard__summary" aria-label="Workspace summary">
            <div class="role-dashboard__summary-card"><span>Account</span><strong>Active</strong></div>
            <div class="role-dashboard__summary-card"><span>Role</span><strong>Super Admin</strong></div>
            <div class="role-dashboard__summary-card"><span>Office</span><strong>PESO Bugallon</strong></div>
            <div class="role-dashboard__summary-card"><span>Workspace</span><strong>In setup</strong></div>
        </section>
        <div class="role-dashboard__workspace-grid">
            <section class="role-dashboard__panel" aria-labelledby="admin-overview-title">
                <header class="role-dashboard__panel-heading"><div><h2 id="admin-overview-title">Program overview</h2><p>Published workforce and training activity</p></div><span class="role-dashboard__plan-label">Not connected</span></header>
                <div class="role-dashboard__empty-state"><span class="role-dashboard__empty-icon" aria-hidden="true">01</span><h3>No programs published yet</h3><p>Program activity will appear here once the catalog is connected and verified content is published.</p></div>
            </section>
            <div class="role-dashboard__sidebar">
                <section class="role-dashboard__panel" aria-labelledby="admin-actions-title">
                    <header class="role-dashboard__panel-heading"><div><h2 id="admin-actions-title">Workspace setup</h2><p>Useful next steps</p></div></header>
                    <div class="role-dashboard__actions">
                        <a class="role-dashboard__action" href="{{ route('profile.show') }}"><span class="role-dashboard__action-mark">↗</span><span class="role-dashboard__action-copy"><strong>Review your profile</strong><small>Keep your account details current.</small></span></a>
                        <div class="role-dashboard__action is-planned"><span class="role-dashboard__action-mark">02</span><span class="role-dashboard__action-copy"><strong>Set up the program catalog</strong><small>Program management is planned.</small></span></div>
                        <div class="role-dashboard__action is-planned"><span class="role-dashboard__action-mark">03</span><span class="role-dashboard__action-copy"><strong>Connect the people directory</strong><small>User management is planned.</small></span></div>
                    </div>
                </section>
                <x-career-chat />
            </div>
        </div>
        <section class="role-dashboard__panel role-dashboard__lower" aria-labelledby="admin-updates-title">
            <header class="role-dashboard__panel-heading"><div><h2 id="admin-updates-title">Recent program updates</h2><p>Changes across published programs</p></div><span class="role-dashboard__plan-label">Awaiting records</span></header>
            <div class="role-dashboard__empty-state"><h3>No updates to show</h3><p>Updates will appear here when program records are available.</p></div>
        </section>
    </x-role-dashboard>
</x-app-layout>
