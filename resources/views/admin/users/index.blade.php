<x-app-layout>
    <div class="dash-title">
        <h1>User directory</h1>
        <p>All registered accounts, including administrator accounts.</p>
    </div>

    <section class="card tbl users-directory" aria-labelledby="users-directory-title">
        <div class="card-head">
            <h2 id="users-directory-title">Database users</h2>
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
                <p>No user accounts are in the database yet.</p>
            </div>
        @endif
    </section>
</x-app-layout>
