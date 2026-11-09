<?php

namespace App\Http\Controllers;

use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    public function index(Request $request, ?User $user = null): View
    {
        $currentUser = $request->user();

        if ($user !== null) {
            abort_unless($user->id !== $currentUser->id && $user->is_active && $user->hasVerifiedEmail(), 404);

            DirectMessage::query()
                ->where('sender_id', $user->id)
                ->where('recipient_id', $currentUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $search = trim((string) $request->query('search', ''));
        $people = User::query()
            ->whereKeyNot($currentUser->id)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $messages = $user === null ? collect() : DirectMessage::query()
            ->where(function ($query) use ($currentUser, $user): void {
                $query->where('sender_id', $currentUser->id)->where('recipient_id', $user->id);
            })
            ->orWhere(function ($query) use ($currentUser, $user): void {
                $query->where('sender_id', $user->id)->where('recipient_id', $currentUser->id);
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->reverse();

        return view('messages.index', compact('people', 'messages', 'user', 'search'));
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $sender = $request->user();
        abort_unless($user->id !== $sender->id && $user->is_active && $user->hasVerifiedEmail(), 404);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $sender->sentDirectMessages()->create([
            'recipient_id' => $user->id,
            'body' => trim($validated['body']),
        ]);
        $user->notify(new WorkspaceNotice(
            'New message from '.$sender->name,
            'Open Messages to read and reply.',
            'message',
            $sender->role->value,
            route('messages.show', $sender),
        ));

        return redirect()->route('messages.show', $user);
    }
}
