<section class="card quick">
    <div class="card-head"><h2>Quick actions</h2></div>
    <div class="qa-grid">
        @foreach ($actions as $a)
            @if ($a['href'])
                <a class="qa" href="{{ $a['href'] }}">
                    <span class="qa-ic"><svg class="ic"><use href="#i-{{ $a['icon'] }}"/></svg></span>
                    <span>{{ $a['label'] }}</span>
                </a>
            @else
                <div class="qa is-soon" aria-disabled="true">
                    <span class="qa-ic"><svg class="ic"><use href="#i-{{ $a['icon'] }}"/></svg></span>
                    <span>{{ $a['label'] }}</span>
                    <em class="chip">Soon</em>
                </div>
            @endif
        @endforeach
    </div>
</section>