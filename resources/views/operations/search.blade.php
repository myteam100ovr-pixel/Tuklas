<form class="operations-search" method="GET" action="{{ url()->current() }}" role="search">
    <label for="operations-search-input">{{ $placeholder }}</label>
    <div class="operations-search__controls">
        <input id="operations-search-input" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="{{ $placeholder }}">
        <button type="submit">Search</button>
        @if (request()->filled('q'))<a href="{{ url()->current() }}">Clear</a>@endif
    </div>
    <p>{{ $count }} {{ \Illuminate\Support\Str::plural('result', $count) }}</p>
</form>
