<section class="qualification-catalog" id="nc-offerings" data-qualification-catalog aria-labelledby="qualification-title">
    <header class="qualification-heading">
        <div><p class="tesda-eyebrow">01 / THE QUALIFICATION DIRECTORY</p><h2 id="qualification-title">Find a qualification.</h2></div>
        <a href="{{ $nationalCatalog['source'] }}" target="_blank" rel="noopener noreferrer">View official source ↗</a>
    </header>
    <p class="qualification-intro">Browse national qualifications from NC I to NC IV. These standards describe training and assessment; they do not confirm open enrollment or availability at a particular school.</p>
    @auth
        @if (auth()->user()->hasRole(\App\Enums\Role::TesdaAdmin))
            <p class="qualification-assign-link">Choose the qualifications you plan to teach. Your choices appear in Teaching.</p>
        @endif
        @if (auth()->user()->hasRole(\App\Enums\Role::TesdaAdmin, \App\Enums\Role::Admin))
            <a class="qualification-assign-link" href="{{ route('tesda.choices.index') }}">Choose training for a youth account ↗</a>
        @endif
        @if ($choiceUser)
            <div class="qualification-plan" role="status">
                <div>
                    <strong>{{ $choiceUser->id === auth()->id() ? 'Your training plan' : $choiceUser->name.'’s training plan' }}</strong>
                    <p>{{ $choiceUser->tesdaChoice ? $choiceUser->tesdaChoice->qualification_title.' · '.$choiceUser->tesdaChoice->nc_level : 'Choose a current qualification below to save a training goal.' }}</p>
                    @if ($choiceUser->tesdaChoice && $choiceUser->tesdaChoice->selected_by !== $choiceUser->id)
                        <small>Chosen by {{ $choiceUser->tesdaChoice->selector?->name ?? 'staff' }}</small>
                    @endif
                </div>
                <span>Planning only · Check provider availability</span>
            </div>
        @endif
        @if (session('status'))<p class="qualification-feedback" role="status">{{ session('status') }}</p>@endif
        @error('qualification_id')<p class="qualification-feedback qualification-feedback--error">{{ $message }}</p>@enderror
    @endauth
    @if ($nationalCatalog['fetched_at'])
        <p class="qualification-checked">Fetched {{ \Carbon\Carbon::parse($nationalCatalog['fetched_at'])->timezone('Asia/Manila')->format('M j, Y') }} · {{ $nationalCatalog['pages'] }} official pages · {{ count($nationalCatalog['qualifications']) }} entries including archived versions</p>
    @endif
    <div class="qualification-filters">
        <label>Find a qualification or location<input type="search" data-qualification-search placeholder="Try cookery, welding, Bugallon, or Lingayen…" aria-controls="qualification-results"></label>
        <label>NC level<select data-qualification-level aria-controls="qualification-results"><option value="">All levels</option>@foreach (['NC I', 'NC II', 'NC III', 'NC IV'] as $level)<option value="{{ $level }}">{{ $level }}</option>@endforeach</select></label>
        <label>Listing<select data-qualification-status aria-controls="qualification-results"><option value="current">Current listings</option><option value="archived">Archived / superseded</option><option value="">All entries</option></select></label>
    </div>
    <p class="qualification-result-count" data-qualification-count role="status">{{ count($nationalCatalog['qualifications']) }} catalog entries</p>
    <div class="qualification-grid" id="qualification-results">
        @foreach ($nationalCatalog['qualifications'] as $qualification)
            <article class="qualification-card" data-qualification data-title="{{ $qualification['title'] }}" data-level="{{ $qualification['nc_level'] }}" data-status="{{ $qualification['status'] }}">
                <div class="qualification-card-top"><span>{{ $qualification['nc_level'] }}</span><small>{{ $qualification['status'] === 'current' ? 'National qualification' : 'Archived / superseded' }}</small></div>
                <h3>{{ $qualification['title'] }}</h3>
                <div class="qualification-location">
                    <strong>{{ match ($qualification['provider_locations_scope'] ?? null) {
                        'nearest_outside_pangasinan' => 'Nearest registered locations outside Pangasinan',
                        'other_official' => 'Other official TESDA locations',
                        default => 'Pangasinan provider locations',
                    } }}</strong>
                    @if (! empty($qualification['provider_locations']))
                        <ul>
                            @foreach (array_slice($qualification['provider_locations'], 0, 3) as $providerLocation)
                                <li>
                                    <strong class="qualification-location__provider">{{ $providerLocation['provider'] ?? 'Training provider' }}</strong>
                                    <small class="qualification-location__address">{{ $providerLocation['address'] ?? 'Not available' }}</small>
                                </li>
                            @endforeach
                        </ul>
                        @if (count($qualification['provider_locations']) > 3)
                            <details class="qualification-location__more">
                                <summary>View {{ count($qualification['provider_locations']) - 3 }} more {{ \Illuminate\Support\Str::plural('location', count($qualification['provider_locations']) - 3) }}</summary>
                                <ul>
                                    @foreach (array_slice($qualification['provider_locations'], 3) as $providerLocation)
                                        <li>
                                            <strong class="qualification-location__provider">{{ $providerLocation['provider'] ?? 'Training provider' }}</strong>
                                            <small class="qualification-location__address">{{ $providerLocation['address'] ?? 'Not available' }}</small>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    @else
                        <p>Not available</p>
                        <a href="{{ $qualification['nearby_provider_url'] }}" target="_blank" rel="noopener noreferrer">Search TESDA listings outside Pangasinan ↗</a>
                    @endif
                </div>
                <p class="qualification-trainers">Trainer: {{ ($qualificationTrainers->get($qualification['id']) ?? collect())->pluck('trainer.name')->filter()->unique()->implode(', ') ?: 'None selected yet' }}</p>
                <div class="qualification-links"><a href="{{ $qualification['source_url'] }}" target="_blank" rel="noopener noreferrer">Training regulations ↗</a>@if ($qualification['status'] === 'current')<a href="{{ $qualification['provider_url'] }}" target="_blank" rel="noopener noreferrer">Pangasinan listings ↗</a>@if (in_array(($qualification['provider_locations_scope'] ?? null), ['nearest_outside_pangasinan', 'other_official'], true))<a href="{{ $qualification['nearby_provider_url'] }}" target="_blank" rel="noopener noreferrer">Other locations ↗</a>@endif @endif</div>
                @if (($choiceUser ?? null) && $qualification['status'] === 'current')
                    <form method="POST" action="{{ route('tesda.choice.store') }}" class="qualification-choice-form">
                        @csrf
                        <input type="hidden" name="qualification_id" value="{{ $qualification['id'] }}">
                        @if ($choiceUser->id !== auth()->id())<input type="hidden" name="user_id" value="{{ $choiceUser->id }}">@endif
                        <button type="submit" @disabled($choiceUser->tesdaChoice?->qualification_id === $qualification['id'])>{{ $choiceUser->tesdaChoice?->qualification_id === $qualification['id'] ? 'Current plan' : 'Choose for training plan' }}</button>
                    </form>
                @endif
                @auth
                    @if (auth()->user()->hasRole(\App\Enums\Role::TesdaAdmin) && $qualification['status'] === 'current')
                        @if ($trainerQualifications->has($qualification['id']))
                            <form method="POST" action="{{ route('trainer.qualifications.destroy', $qualification['id']) }}" class="qualification-choice-form">@csrf @method('DELETE')<button type="submit">Remove from teaching</button></form>
                        @else
                            <form method="POST" action="{{ route('trainer.qualifications.store') }}" class="qualification-choice-form">@csrf<input type="hidden" name="qualification_id" value="{{ $qualification['id'] }}"><button type="submit">Choose to teach</button></form>
                        @endif
                    @endif
                @endauth
            </article>
        @endforeach
    </div>
    <p class="qualification-empty" data-qualification-empty hidden>No matching qualifications. Try a broader search or another NC level.</p>
    @if (count($nationalCatalog['qualifications']) === 0)<p>The national catalog is currently unavailable. Use the official source above to browse qualifications.</p>@endif
</section>
