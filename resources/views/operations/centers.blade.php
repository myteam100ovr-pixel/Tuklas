<x-app-layout><div class="trainer-courses suite-page">
    <x-workspace-heading eyebrow="TESDA · CENTERS" title="Training centers" description="Participating PTC and PSAT institutions." />
    @if (session('status'))<p role="status">{{ session('status') }}</p>@endif @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if ($canManage)<section class="trainer-courses__card"><h2>Add center</h2><form method="POST" action="{{ route('centers.store') }}">@csrf
        <label>Name <input name="name" required></label><label>Type <select name="type"><option>PTC</option><option>PSAT</option></select></label>
        <label>Address <input name="address"></label><label>Email <input type="email" name="contact_email"></label><label>Phone <input name="contact_phone"></label>
        <label>Status <select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></label><button type="submit">Save center</button>
    </form></section>@endif
    @forelse ($centers as $center)<article class="trainer-courses__card"><h2>{{ $center->name }} · {{ $center->type }}</h2><p>{{ $center->address }}</p><p>{{ $center->contact_email }} {{ $center->contact_phone }}</p>
        @if ($canManage)<details><summary>Edit center</summary><form method="POST" action="{{ route('centers.update', $center) }}">@csrf @method('PUT')
            <label>Name <input name="name" required value="{{ $center->name }}"></label><label>Type <select name="type"><option @selected($center->type === 'PTC')>PTC</option><option @selected($center->type === 'PSAT')>PSAT</option></select></label>
            <label>Address <input name="address" value="{{ $center->address }}"></label><label>Email <input type="email" name="contact_email" value="{{ $center->contact_email }}"></label><label>Phone <input name="contact_phone" value="{{ $center->contact_phone }}"></label>
            <label>Status <select name="is_active"><option value="1" @selected($center->is_active)>Active</option><option value="0" @selected(! $center->is_active)>Inactive</option></select></label><button type="submit">Update</button>
        </form></details>@endif
    </article>@empty <p>No training centers yet.</p> @endforelse
    {{ $centers->links() }}
</div></x-app-layout>
