<div class="card-head"><h2>{{ $table['title'] }}</h2></div>

@if (count($table['rows']) > 0)
    <div class="tbl-wrap">
        <table class="tbl-t">
            <thead>
                <tr>
                    @foreach ($table['columns'] as $c)<th scope="col">{{ $c }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($table['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>
                                @if (! empty($cell['chip']))
                                    <span class="chip chip-{{ $cell['tone'] }}">{{ $cell['chip'] }}</span>
                                @else
                                    <div class="cell">
                                        @if (! empty($cell['avatar']))<span class="av">{{ $cell['avatar'] }}</span>@endif
                                        <div>
                                            <span class="cell-t">{{ $cell['t'] }}</span>
                                            @if (! empty($cell['s']))<span class="cell-s">{{ $cell['s'] }}</span>@endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="empty">
        <svg class="ic"><use href="#i-list"/></svg>
        <p>{{ $table['empty'] }}</p>
    </div>
@endif