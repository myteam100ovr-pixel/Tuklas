<div class="card-head"><h2>{{ $tasks['title'] }}</h2></div>
<ul class="task-list">
    @foreach ($tasks['items'] as $t)
        <li class="task task-{{ $t['state'] }}">
            <span class="task-ic" aria-hidden="true">
                @if ($t['state'] === 'done')<svg class="ic"><use href="#i-check"/></svg>@endif
            </span>
            <div class="task-body">
                <b>{{ $loop->iteration }}. {{ $t['title'] }}</b>
                <span class="task-meta">{{ match ($t['state']) { 'done' => 'Done', 'ongoing' => 'In progress', 'waiting' => 'Waiting for TESDA', default => 'To do' } }}</span>
                <p>{{ $t['description'] }}</p>
            </div>
            @if (! empty($t['href']))
                <a class="task-go" href="{{ $t['href'] }}" aria-label="Open: {{ $t['title'] }}"><svg class="ic"><use href="#i-chev"/></svg></a>
            @endif
        </li>
    @endforeach
</ul>
