@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="tk-modal-in">
        <h3 class="tk-modal-title">{{ $title }}</h3>
        <div class="tk-modal-content">{{ $content }}</div>
    </div>

    <div class="tk-modal-foot">{{ $footer }}</div>
</x-modal>