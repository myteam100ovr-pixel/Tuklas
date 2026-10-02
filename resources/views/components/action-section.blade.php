<section {{ $attributes->merge(['class' => 'card tk-sec']) }}>
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <div class="tk-sec-form">{{ $content }}</div>
</section>