<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="PESO · EMPLOYERS" title="Employers" description="Verify employers before publishing their opportunities." />
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
        <section class="trainer-courses__card"><h2>Add employer</h2>
            <form method="POST" action="{{ route('admin.employers.store') }}">@csrf
                <label>Name <input name="name" required maxlength="255" value="{{ old('name') }}"></label>
                <label>Location <input name="location" maxlength="255" value="{{ old('location') }}"></label>
                <label>Contact email <input name="contact_email" type="email" value="{{ old('contact_email') }}"></label>
                <label>Website <input name="website" type="url" value="{{ old('website') }}"></label>
                <input name="is_verified" type="hidden" value="0"><label><input name="is_verified" type="checkbox" value="1"> Verified</label>
                <button class="trainer-courses__primary" type="submit">Save employer</button>
            </form>
        </section>
        @forelse ($employers as $employer)
            <section class="trainer-courses__card"><h2>{{ $employer->name }}</h2><p>{{ $employer->location }} · {{ $employer->is_verified ? 'Verified' : 'Unverified' }}</p>
                <form method="POST" action="{{ route('admin.employers.update', $employer) }}">@csrf @method('PUT')
                    <label>Name <input name="name" required value="{{ $employer->name }}"></label><label>Location <input name="location" value="{{ $employer->location }}"></label>
                    <label>Contact email <input name="contact_email" type="email" value="{{ $employer->contact_email }}"></label><label>Website <input name="website" type="url" value="{{ $employer->website }}"></label>
                    <label>Verified <select name="is_verified"><option value="1" @selected($employer->is_verified)>Yes</option><option value="0" @selected(! $employer->is_verified)>No</option></select></label>
                    <button type="submit">Update</button>
                </form>
            </section>
        @empty <p>No employers yet.</p> @endforelse
        {{ $employers->links() }}
    </div>
</x-app-layout>
