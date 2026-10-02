@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'alert alert-bad']) }} role="alert">
        <strong>{{ __('Whoops! Something went wrong.') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif