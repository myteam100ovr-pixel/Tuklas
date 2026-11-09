<?php

namespace App\Http\Controllers;

use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    public function mobileIndex(Request $request, ?User $user = null): JsonResponse
    {
        $currentUser = $request->user();

        if ($user !== null) {
            abort_unless($user->id !== $currentUser->id && $user->is_active && $user->hasVerifiedEmail(), 404);
            $after = $request->validate(['after' => ['sometimes', 'integer', 'min:0']])['after'] ?? null;
            DirectMessage::query()->where('sender_id', $user->id)->where('recipient_id', $currentUser->id)->whereNull('read_at')->update(['read_at' => now()]);
            $conversation = DirectMessage::query()
                ->where(fn ($query) => $query
                    ->where(fn ($direction) => $direction->where('sender_id', $currentUser->id)->where('recipient_id', $user->id))
                    ->orWhere(fn ($direction) => $direction->where('sender_id', $user->id)->where('recipient_id', $currentUser->id)));
            $messages = ($after === null
                ? $conversation->latest('id')->limit(100)->get()->reverse()->values()
                : $conversation->where('id', '>', $after)->oldest('id')->limit(100)->get())
                ->map(fn (DirectMessage $message): array => ['id' => $message->id, 'sender_id' => $message->sender_id, 'body' => $message->body, 'created_at' => $message->created_at->toIso8601String()]);

            return response()->json(['person' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role->label()], 'messages' => $messages]);
        }

        $search = trim((string) $request->query('search', ''));
        $people = User::query()->whereKeyNot($currentUser->id)->where('is_active', true)->whereNotNull('email_verified_at')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")->orderBy('name')->limit(100)->get()
            ->map(fn (User $person): array => ['id' => $person->id, 'name' => $person->name, 'role' => $person->role->label()]);

        return response()->json(['people' => $people]);
    }

    public function mobileStore(Request $request, User $user): JsonResponse
    {
        $sender = $request->user();
        abort_unless($user->id !== $sender->id && $user->is_active && $user->hasVerifiedEmail(), 404);
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $message = $sender->sentDirectMessages()->create(['recipient_id' => $user->id, 'body' => trim($validated['body'])]);
        $user->notify(new WorkspaceNotice('New message from '.$sender->name, 'Open Messages to read and reply.', 'message', $sender->role->value, route('messages.show', $sender)));

        return response()->json(['message' => ['id' => $message->id, 'sender_id' => $message->sender_id, 'body' => $message->body, 'created_at' => $message->created_at->toIso8601String()]], 201);
    }

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

    public function store(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $sender = $request->user();
        abort_unless($user->id !== $sender->id && $user->is_active && $user->hasVerifiedEmail(), 404);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $message = $sender->sentDirectMessages()->create([
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

        if ($request->expectsJson()) {
            return response()->json(['message' => ['id' => $message->id, 'sender_id' => $message->sender_id, 'body' => $message->body, 'created_at' => $message->created_at->toIso8601String()]], 201);
        }

        return redirect()->route('messages.show', $user);
    }
}
