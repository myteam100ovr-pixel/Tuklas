<x-app-layout>
    <x-role-dashboard accent="trainer" role-label="TESDA Lingayen · Trainer" eyebrow="Training workspace / Trainer" title="Make every learning session count." description="Prepare cohorts, support learners, and keep skills training on track from one workspace." :focus-areas="['Cohorts', 'Sessions', 'Resources']">
        <section class="role-dashboard__summary" aria-label="Workspace summary">
            <div class="role-dashboard__summary-card"><span>Account</span><strong>Active</strong></div>
            <div class="role-dashboard__summary-card"><span>Role</span><strong>Trainer</strong></div>
            <div class="role-dashboard__summary-card"><span>Institution</span><strong>TESDA Lingayen</strong></div>
            <div class="role-dashboard__summary-card"><span>Training tools</span><strong>In setup</strong></div>
        </section>
        <div class="role-dashboard__workspace-grid">
            <section class="role-dashboard__panel" aria-labelledby="trainer-progress-title">
                <header class="role-dashboard__panel-heading"><div><h2 id="trainer-progress-title">Learner progress</h2><p>Cohort milestones and course activity</p></div><span class="role-dashboard__plan-label">Not connected</span></header>
                <div class="role-dashboard__empty-state"><span class="role-dashboard__empty-icon" aria-hidden="true">01</span><h3>No cohorts assigned yet</h3><p>Your learners and course activity will appear here when cohort assignments are available.</p></div>
            </section>
            <div class="role-dashboard__sidebar">
                <section class="role-dashboard__panel" aria-labelledby="trainer-actions-title">
                    <header class="role-dashboard__panel-heading"><div><h2 id="trainer-actions-title">Quick steps</h2><p>Get your workspace ready</p></div></header>
                    <div class="role-dashboard__actions">
                        <a class="role-dashboard__action" href="{{ route('profile.show') }}"><span class="role-dashboard__action-mark">↗</span><span class="role-dashboard__action-copy"><strong>Review your profile</strong><small>Check your account details.</small></span></a>
                        <div class="role-dashboard__action is-planned"><span class="role-dashboard__action-mark">02</span><span class="role-dashboard__action-copy"><strong>Plan a session</strong><small>Session planning is coming later.</small></span></div>
                        <div class="role-dashboard__action is-planned"><span class="role-dashboard__action-mark">03</span><span class="role-dashboard__action-copy"><strong>Open course resources</strong><small>Trainer resources are planned.</small></span></div>
                    </div>
                </section>
                <x-career-chat />
            </div>
        </div>
        <section class="role-dashboard__panel role-dashboard__lower" aria-labelledby="trainer-cohorts-title">
            <header class="role-dashboard__panel-heading"><div><h2 id="trainer-cohorts-title">Assigned cohorts</h2><p>Groups connected to your training account</p></div><span class="role-dashboard__plan-label">Awaiting assignments</span></header>
            <div class="role-dashboard__empty-state"><h3>No cohort records to show</h3><p>Assigned groups will appear here once cohort management is connected.</p></div>
        </section>
    </x-role-dashboard>
</x-app-layout>
