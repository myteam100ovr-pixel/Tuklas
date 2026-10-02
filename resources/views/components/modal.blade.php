@props(['id' => null, 'maxWidth' => null])

@php
$id = $id ?? md5($attributes->wire('model'));

$maxWidth = [
    'sm' => '24rem',
    'md' => '28rem',
    'lg' => '32rem',
    'xl' => '36rem',
    '2xl' => '42rem',
][$maxWidth ?? '2xl'];
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    id="{{ $id }}"
    class="tk-modal"
    style="display: none;"
>
    <div class="tk-modal-back" x-show="show" x-on:click="show = false" x-transition.opacity></div>

    <div class="tk-modal-box" style="max-width: {{ $maxWidth }}" x-show="show" x-trap.inert.noscroll="show" x-transition>
        {{ $slot }}
    </div>
</div>