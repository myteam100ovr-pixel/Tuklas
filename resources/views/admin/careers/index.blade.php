<x-app-layout>
    <div class="trainer-courses suite-page">
        <x-workspace-heading eyebrow="PESO · CAREERS" title="Career paths" description="Maintain career guidance shown in Tuklas. Publish only paths backed by a reliable source." />
        @if (session('status'))<p class="trainer-courses__feedback" role="status">{{ session('status') }}</p>@endif
        <div class="trainer-courses__toolbar">
            <div class="flex flex-wrap gap-3 text-sm">
                @foreach (['All' => null, 'Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'] as $label => $value)
                    <a href="{{ $value ? route('admin.careers.index', ['status' => $value]) : route('admin.careers.index') }}" @if ($statusFilter === $value) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </div>
            <a href="{{ route('admin.careers.create') }}" class="trainer-courses__primary">Add career path <span aria-hidden="true">↗</span></a>
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-300"><a class="underline" href="{{ route('careers.index') }}">View published career paths ↗</a></p>
        <section aria-label="Career paths">
            <div class="trainer-courses__section-heading"><h2>{{ $careers->total() }} career paths</h2></div>
            @forelse ($careers as $career)
                <article class="trainer-courses__card">
                    <div>
                        <span class="trainer-courses__status">{{ ucfirst($career->status) }}</span>
                        <h3>{{ $career->title }}</h3>
                        <p>{{ $career->education_level ?: 'Education level not specified' }} · {{ $career->source_note ?: 'Source not recorded' }}</p>
                    </div>
                    <a href="{{ route('admin.careers.edit', $career) }}">Edit path <span aria-hidden="true">↗</span></a>
                </article>
            @empty
                <div class="trainer-courses__empty">No career paths match this view. Add one as a draft to get started.</div>
            @endforelse
            {{ $careers->links() }}
        </section>
    </div>
</x-app-layout>
