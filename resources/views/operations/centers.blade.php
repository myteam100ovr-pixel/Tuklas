<x-app-layout><div class="trainer-courses suite-page operations-page">
    <x-workspace-heading eyebrow="TESDA · CENTERS" title="Training centers" description="Participating PTC and PSAT institutions." />
    @include('operations.search', ['placeholder' => 'Search centers or places', 'count' => $centers->total()])
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if ($canManage)@php($creatingCenter = old('_operation') === 'create-center')<section class="operations-card"><h2>Add center</h2><form method="POST" action="{{ route('centers.store') }}">@csrf<input type="hidden" name="_operation" value="create-center">
        <label>Name <input name="name" required value="{{ $creatingCenter ? old('name') : '' }}"></label><label>Type <select name="type"><option @selected(! $creatingCenter || old('type') === 'PTC')>PTC</option><option @selected($creatingCenter && old('type') === 'PSAT')>PSAT</option></select></label>
        <label>Address <input name="address" value="{{ $creatingCenter ? old('address') : '' }}"></label><label>Email <input type="email" name="contact_email" value="{{ $creatingCenter ? old('contact_email') : '' }}"></label><label>Phone <input name="contact_phone" value="{{ $creatingCenter ? old('contact_phone') : '' }}"></label>
        <label>Status <select name="is_active"><option value="1" @selected(! $creatingCenter || old('is_active') === '1')>Active</option><option value="0" @selected($creatingCenter && old('is_active') === '0')>Inactive</option></select></label><button type="submit">Save center</button>
    </form></section>@endif
    @forelse ($centers as $center)<article class="operations-card"><h2>{{ $center->name }} <span class="operations-status">{{ $center->type }}</span></h2><p>{{ $center->address }}</p><p>{{ $center->contact_email }} {{ $center->contact_phone }}</p>
        <section aria-label="{{ $center->name }} program offerings"><h3>Program offerings <span class="operations-status">{{ $center->programs->count() }}</span></h3>
            @forelse ($center->programs as $program)<p><strong>{{ $program->title }}</strong> · {{ $program->duration_days ? $program->duration_days.' training days' : 'Duration not specified' }} @if ($canManage)<span class="operations-status">{{ ucfirst($program->status) }}</span>@endif</p>
            @empty<p class="operations-empty">{{ $canManage ? 'No program offerings loaded for this center yet.' : 'No published offerings listed yet.' }}</p>@endforelse
        </section>
        @if ($canManage)<details><summary>Edit center</summary><form method="POST" action="{{ route('centers.update', $center) }}">@csrf @method('PUT')
            <label>Name <input name="name" required value="{{ $center->name }}"></label><label>Type <select name="type"><option @selected($center->type === 'PTC')>PTC</option><option @selected($center->type === 'PSAT')>PSAT</option></select></label>
            <label>Address <input name="address" value="{{ $center->address }}"></label><label>Email <input type="email" name="contact_email" value="{{ $center->contact_email }}"></label><label>Phone <input name="contact_phone" value="{{ $center->contact_phone }}"></label>
            <label>Status <select name="is_active"><option value="1" @selected($center->is_active)>Active</option><option value="0" @selected(! $center->is_active)>Inactive</option></select></label><button type="submit">Update</button>
        </form></details>@endif
    </article>@empty <p class="operations-empty">{{ request()->filled('q') ? 'No centers match your search.' : 'No training centers yet.' }}</p> @endforelse
    {{ $centers->links() }}
</div></x-app-layout>
