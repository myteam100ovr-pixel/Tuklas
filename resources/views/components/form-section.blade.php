@props(['submit'])

<section {{ $attributes->merge(['class' => 'card tk-sec']) }}>
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <form wire:submit="{{ $submit }}" class="tk-sec-form">
        {{ $form }}

        @if (isset($actions))
            <div class="tk-sec-actions">{{ $actions }}</div>
        @endif
    </form>
</section>