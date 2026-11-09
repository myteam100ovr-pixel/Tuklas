<x-app-layout>
    <div class="suite-page messages-page">
        <x-workspace-heading eyebrow="WORKSPACE · PEOPLE" title="Messages" description="Talk directly with Tuklas members and administrators." />

        <div class="grid min-w-0 gap-5 lg:grid-cols-[minmax(16rem,20rem)_minmax(0,1fr)]">
            <aside class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900" aria-label="People">
                <form method="GET" action="{{ route('messages.index') }}" class="mb-4 flex gap-2">
                    <label for="message-search" class="sr-only">Find a person</label>
                    <input id="message-search" name="search" type="search" value="{{ $search }}" placeholder="Find a person" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                    <button type="submit" class="rounded-xl bg-violet-700 px-3 py-2 text-sm font-semibold text-white">Find</button>
                </form>
                <div class="grid gap-2">
                    @forelse ($people as $person)
                        <a href="{{ route('messages.show', $person) }}" class="rounded-xl border p-3 transition hover:border-violet-400 hover:bg-violet-50 dark:hover:bg-slate-800 {{ $user?->id === $person->id ? 'border-violet-500 bg-violet-50 dark:bg-slate-800' : 'border-slate-200 dark:border-slate-700' }}">
                            <strong class="block text-sm">{{ $person->name }}</strong>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $person->role->label() }}</span>
                        </a>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No people found.</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $people->links() }}</div>
            </aside>

            <section class="flex min-h-[28rem] min-w-0 flex-col rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900" aria-label="Conversation">
                @if ($user)
                    <header class="border-b border-slate-200 p-4 dark:border-slate-700">
                        <h2 class="text-lg font-semibold">{{ $user->name }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->role->label() }}</p>
                    </header>
                    <div class="flex max-h-[65vh] flex-1 flex-col gap-3 overflow-y-auto overscroll-contain p-4" data-message-thread data-updates-url="{{ route('messages.updates', $user) }}" data-sender-id="{{ auth()->id() }}" aria-live="polite">
                        @forelse ($messages as $message)
                            <article data-message-id="{{ $message->id }}" class="max-w-[85%] rounded-2xl px-4 py-3 {{ $message->sender_id === auth()->id() ? 'self-end bg-violet-700 text-white' : 'self-start bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white' }}">
                                <p class="whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>
                                <time class="mt-2 block text-xs opacity-75" datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, g:i A') }}</time>
                            </article>
                        @empty
                            <p data-message-empty class="m-auto text-center text-sm text-slate-500">No messages yet. Say hello.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('messages.store', $user) }}" data-message-form class="border-t border-slate-200 p-4 dark:border-slate-700">
                        @csrf
                        <label for="message-body" class="mb-2 block text-sm font-semibold">Your message</label>
                        <textarea id="message-body" name="body" rows="3" maxlength="2000" required class="w-full rounded-xl border border-slate-300 p-3 text-sm dark:border-slate-600 dark:bg-slate-800">{{ old('body') }}</textarea>
                        @error('body') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                        <p data-message-status class="mt-2 text-sm text-red-600 dark:text-red-400" role="status" aria-live="polite"></p>
                        <div class="mt-3 flex justify-end"><button type="submit" class="rounded-xl bg-violet-700 px-5 py-2.5 font-semibold text-white hover:bg-violet-800 disabled:opacity-50">Send message</button></div>
                    </form>
                @else
                    <p class="m-auto p-6 text-center text-slate-500">Choose a person to start a conversation.</p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
