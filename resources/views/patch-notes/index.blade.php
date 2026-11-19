<x-app-layout>
    <div class="patch-notes-page">
        <x-workspace-heading eyebrow="TUKLAS · UPDATES" title="Patch notes." description="See what has changed across the Tuklas website and mobile app, with the latest update first." />

        <div class="patch-notes-list">
            @foreach ($patchNotes as $patch)
                <article class="patch-notes-card">
                    <div class="patch-notes-card__meta">
                        <time datetime="{{ $patch['date'] }}">{{ \Illuminate\Support\Carbon::parse($patch['date'])->format('F j, Y') }}</time>
                        <span>{{ $patch['version'] }}</span>
                    </div>
                    <h2>{{ $patch['title'] }}</h2>
                    <ul>
                        @foreach ($patch['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
