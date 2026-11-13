<x-app-layout>
    <div class="suite-page suite-people">
    <x-workspace-heading eyebrow="COMMUNITY · PEOPLE" title="Manage Tuklas accounts" description="Create, edit, restore, and reset accounts across every role." />

    @if (session('status'))<p class="trainer-courses__feedback" role="status">{{ session('status') }}</p>@endif
    @error('reset')<p class="trainer-courses__error" role="alert">{{ $message }}</p>@enderror
    <div class="users-directory__toolbar">
        <div><a href="{{ route('admin.users.index') }}" @if (request('status') !== 'deleted') aria-current="page" @endif>Active accounts</a><a href="{{ route('admin.users.index', ['status' => 'deleted']) }}" @if (request('status') === 'deleted') aria-current="page" @endif>Deleted accounts</a></div>
        <a class="trainer-courses__primary" href="{{ route('admin.users.create') }}">Add a user</a>
    </div>

    <form class="operations-search" method="GET" action="{{ route('admin.users.index') }}" role="search">
        @if (request('status') === 'deleted')<input type="hidden" name="status" value="deleted">@endif
        <label for="users-name-search">Search users by name</label>
        <div class="operations-search__controls">
            <input id="users-name-search" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="Enter a user’s name">
            <button type="submit">Search</button>
            @if (request()->filled('q'))<a href="{{ route('admin.users.index', request('status') === 'deleted' ? ['status' => 'deleted'] : []) }}">Clear</a>@endif
        </div>
        <p>{{ number_format($users->total()) }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }} found</p>
    </form>

    <section class="card tbl users-directory" aria-labelledby="users-directory-title">
        <div class="card-head">
            <h2 id="users-directory-title">{{ request('status') === 'deleted' ? 'Deleted accounts' : 'Users directory' }}</h2>
            <span class="users-directory__count">{{ number_format($users->total()) }} total</span>
        </div>

        @if ($users->isNotEmpty())
            <div class="tbl-wrap">
                <table class="tbl-t users-directory__table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Account status</th>
                            <th scope="col">Email status</th>
                            <th scope="col">Joined</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role->label() }}</td>
                                <td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                                <td>{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</td>
                                <td>{{ $user->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="users-directory__actions">
                                        @if (request('status') === 'deleted')
                                            <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">@csrf<button type="submit">Restore</button></form>
                                        @else
                                            <a href="{{ route('admin.users.edit', $user) }}">Edit</a>
                                            @if ($user->is_active && $user->hasVerifiedEmail() && $user->id !== auth()->id())<a href="{{ route('messages.show', $user) }}">Message</a>@endif
                                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">@csrf<button type="submit">Reset password</button></form>
                                            @if ($user->id !== auth()->id())
                                                <details><summary>Delete</summary><form method="POST" action="{{ route('admin.users.destroy', $user) }}">@csrf @method('DELETE')<p>Delete {{ $user->name }}? You can restore this account later.</p><button type="submit">Confirm delete</button></form></details>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <nav class="users-directory__pagination" aria-label="User directory pages">
                    @if ($users->previousPageUrl())
                        <a href="{{ $users->previousPageUrl() }}" rel="prev">Previous</a>
                    @else
                        <span aria-disabled="true">Previous</span>
                    @endif

                    <span>Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>

                    @if ($users->nextPageUrl())
                        <a href="{{ $users->nextPageUrl() }}" rel="next">Next</a>
                    @else
                        <span aria-disabled="true">Next</span>
                    @endif
                </nav>
            @endif
        @else
            <div class="empty">
                <svg class="ic" aria-hidden="true"><use href="#i-users"/></svg>
                <p>{{ request()->filled('q') ? 'No users match that name.' : (request('status') === 'deleted' ? 'No deleted accounts found.' : 'No user accounts are in the database yet.') }}</p>
            </div>
        @endif
    </section>
    </div>
</x-app-layout>
