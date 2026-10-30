<x-app-layout>
    <div class="suite-page suite-tesda">
        <x-workspace-heading eyebrow="TESDA · TRAINING PLANS" title="Choose who you’re guiding" description="Select a youth account, then choose a current TESDA qualification as their training goal. This does not enroll them with a provider." />
        <section class="card tbl" aria-label="Youth training plans">
            <div class="tbl-wrap">
                <table class="tbl-t">
                    <thead><tr><th scope="col">Youth account</th><th scope="col">Current training plan</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                        @forelse ($youthUsers as $youth)
                            <tr>
                                <td>{{ $youth->name }}</td>
                                <td>{{ $youth->tesdaChoice?->qualification_title ?? 'No plan yet' }}</td>
                                <td><a class="qualification-assign-link" href="{{ route('tesda.index', ['for_user' => $youth->id]) }}#nc-offerings">Choose qualification ↗</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No active youth accounts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $youthUsers->links() }}
        </section>
    </div>
</x-app-layout>
