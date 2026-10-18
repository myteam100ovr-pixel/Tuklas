@php
    $status = $exception->getStatusCode();
    [$heading, $message] = match ($status) {
        403 => ['Access unavailable', 'Your account does not have access to this page. If your account was disabled, contact your administrator.'],
        404 => ['Page not found', 'This page may have moved or is no longer available.'],
        419 => ['Your session expired', 'Reload the page and sign in again before submitting. Your last submission may not have been saved.'],
        429 => ['A little too many requests', 'Please wait a moment before trying again.'],
        default => ['Something went wrong', 'We could not complete this request. Please try again shortly.'],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} · Tuklas</title>
    <style>
        * { box-sizing: border-box; } body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; background:#f6f4fa; color:#25163d; font:16px/1.65 system-ui,sans-serif; } main { width:100%; max-width:580px; padding:clamp(24px,6vw,56px); background:white; border:1px solid #e4deef; border-radius:24px; } small { color:#6b428f; letter-spacing:.1em; } h1 { font-size:clamp(28px,6vw,40px); line-height:1.2; } p { color:#61596c; } a { display:inline-block; margin-top:16px; padding:12px 22px; border-radius:10px; background:#48207c; color:white; text-decoration:none; } a:focus-visible { outline:3px solid #9771d7; outline-offset:4px; }
    </style>
</head>
<body><main><small>TUKLAS / {{ $status }}</small><h1>{{ $heading }}</h1><p>{{ $message }}</p><a href="{{ route('home') }}">Return to home</a></main></body>
</html>
