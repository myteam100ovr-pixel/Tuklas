<section class="qualification-catalog" id="nc-offerings" data-qualification-catalog aria-labelledby="qualification-title">
    <header class="qualification-heading">
        <div><p class="tesda-eyebrow">OFFICIAL TRAINING REGULATIONS</p><h2 id="qualification-title">One catalog. More possibilities.</h2></div>
        <a href="{{ $nationalCatalog['source'] }}" target="_blank" rel="noopener noreferrer">View official source ↗</a>
    </header>
    <p class="qualification-intro">Browse national qualifications from NC I to NC IV. These standards describe training and assessment; they do not confirm open enrollment or availability at a particular school.</p>
    @if ($nationalCatalog['fetched_at'])
        <p class="qualification-checked">Fetched {{ \Carbon\Carbon::parse($nationalCatalog['fetched_at'])->timezone('Asia/Manila')->format('M j, Y') }} · {{ $nationalCatalog['pages'] }} official pages · {{ count($nationalCatalog['qualifications']) }} entries including archived versions</p>
    @endif
    <div class="qualification-filters">
        <label>Find a qualification<input type="search" data-qualification-search placeholder="Try cookery, welding, agriculture…" aria-controls="qualification-results"></label>
        <label>NC level<select data-qualification-level aria-controls="qualification-results"><option value="">All levels</option>@foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)<option value="{{ $level }}">{{ $level }}</option>@endforeach</select></label>
        <label>Listing<select data-qualification-status aria-controls="qualification-results"><option value="current">Current listings</option><option value="archived">Archived / superseded</option><option value="">All entries</option></select></label>
    </div>
    <p class="qualification-result-count" data-qualification-count role="status">{{ count($nationalCatalog['qualifications']) }} catalog entries</p>
    <div class="qualification-grid" id="qualification-results">
        @foreach ($nationalCatalog['qualifications'] as $qualification)
            <article class="qualification-card" data-qualification data-title="{{ $qualification['title'] }}" data-level="{{ $qualification['nc_level'] }}" data-status="{{ $qualification['status'] }}">
                <div class="qualification-card-top"><span>{{ $qualification['nc_level'] }}</span><small>{{ $qualification['status'] === 'current' ? 'National qualification' : 'Archived / superseded' }}</small></div>
                <h3>{{ $qualification['title'] }}</h3>
                <div class="qualification-links"><a href="{{ $qualification['source_url'] }}" target="_blank" rel="noopener noreferrer">Training regulations ↗</a>@if ($qualification['status'] === 'current')<a href="{{ $qualification['provider_url'] }}" target="_blank" rel="noopener noreferrer">Find a provider ↗</a>@endif</div>
            </article>
        @endforeach
    </div>
    <p class="qualification-empty" data-qualification-empty hidden>No matching qualifications. Try a broader search or another NC level.</p>
    @if (count($nationalCatalog['qualifications']) === 0)<p>The national catalog is currently unavailable. Use the official source above to browse qualifications.</p>@endif
</section>
