<?php

namespace App\Http\Controllers;

use App\Events\DirectMessageSent;
use App\Events\WorkspaceChanged;
use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                ->map(fn (DirectMessage $message): array => $this->messageData($message));

            return response()->json([
                'person' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role->label()],
                'messages' => $messages,
                'realtime' => $this->realtimeConfiguration($request),
            ]);
        }

        $search = trim((string) $request->query('search', ''));
        $people = User::query()->whereKeyNot($currentUser->id)->where('is_active', true)->whereNotNull('email_verified_at')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")->orderBy('name')->limit(100)->get()
            ->map(fn (User $person): array => ['id' => $person->id, 'name' => $person->name, 'role' => $person->role->label()]);

        return response()->json(['people' => $people]);
    }

    public function mobileStore(Request $request, User $user): JsonResponse
    {
        $sender = $request->user();
        abort_unless($user->id !== $sender->id && $user->is_active && $user->hasVerifiedEmail(), 404);
        $validated = $this->validateMessage($request);
        $attachments = $this->storeAttachments($request, $sender->id, $user->id);
        $message = $sender->sentDirectMessages()->create([
            'recipient_id' => $user->id,
            'body' => trim((string) ($validated['body'] ?? '')),
            'attachments' => $attachments,
        ]);
        $this->broadcastMessage($message);
        $user->notify(new WorkspaceNotice('New message from '.$sender->name, 'Open Messages to read and reply.', 'message', $sender->role->value, route('messages.show', $sender)));

        return response()->json(['message' => $this->messageData($message)], 201);
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

        $validated = $this->validateMessage($request);
        $attachments = $this->storeAttachments($request, $sender->id, $user->id);
        $message = $sender->sentDirectMessages()->create([
            'recipient_id' => $user->id,
            'body' => trim((string) ($validated['body'] ?? '')),
            'attachments' => $attachments,
        ]);
        $this->broadcastMessage($message);
        $user->notify(new WorkspaceNotice(
            'New message from '.$sender->name,
            'Open Messages to read and reply.',
            'message',
            $sender->role->value,
            route('messages.show', $sender),
        ));

        if ($request->expectsJson()) {
            return response()->json(['message' => $this->messageData($message)], 201);
        }

        return redirect()->route('messages.show', $user);
    }

    /** @return array{enabled: bool, key: ?string, host: string, port: int, scheme: string} */
    public function mobileRealtime(Request $request): JsonResponse
    {
        return response()->json(['realtime' => $this->realtimeConfiguration($request)]);
    }

    public function downloadAttachment(
        Request $request,
        User $user,
        DirectMessage $message,
        int $attachment,
    ): StreamedResponse {
        $currentUserId = $request->user()->id;
        $isConversationParticipant = (
            $message->sender_id === $currentUserId && $message->recipient_id === $user->id
        ) || (
            $message->recipient_id === $currentUserId && $message->sender_id === $user->id
        );
        abort_unless($isConversationParticipant, 403);

        $file = $message->attachments[$attachment] ?? null;
        abort_unless(is_array($file) && isset($file['path'], $file['name']), 404);
        abort_unless(Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], basename($file['name']), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array<string, mixed> */
    private function validateMessage(Request $request): array
    {
        return $request->validate([
            'body' => ['nullable', 'string', 'max:2000', 'required_without:attachments'],
            'attachments' => ['required_without:body', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ]);
    }

    /** @return list<array{name: string, path: string, mime: string, size: int}> */
    private function storeAttachments(Request $request, int $senderId, int $recipientId): array
    {
        return collect($request->file('attachments', []))
            ->map(
                fn ($file): array => [
                    'name' => basename($file->getClientOriginalName()),
                    'path' => $file->store("direct-messages/{$senderId}/{$recipientId}", 'local'),
                    'mime' => (string) $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                ],
            )
            ->all();
    }

    /** @return array{id: int, sender_id: int, recipient_id: int, body: string, attachments: list<array{index: int, name: string, mime: string, size: int}>, created_at: string} */
    private function messageData(DirectMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'body' => $message->body,
            'attachments' => collect($message->attachments ?? [])
                ->map(fn (array $file, int $index): array => [
                    'index' => $index,
                    'name' => $file['name'],
                    'mime' => $file['mime'],
                    'size' => $file['size'],
                ])
                ->values()
                ->all(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /** @return array{enabled: bool, key: ?string, host: string, port: int, scheme: string} */
    private function realtimeConfiguration(Request $request): array
    {
        $isSecure = $request->isSecure();
        $port = $isSecure ? 443 : (int) config('reverb.servers.reverb.port', 8080);

        return [
            'enabled' => config('broadcasting.default') === 'reverb' && filled(config('broadcasting.connections.reverb.key')),
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => $request->getHost(),
            'port' => $port,
            'scheme' => $isSecure ? 'wss' : 'ws',
        ];
    }

    private function broadcastMessage(DirectMessage $message): void
    {
        try {
            broadcast(new DirectMessageSent($message));
            WorkspaceChanged::dispatch('messages');
        } catch (\Throwable $exception) {
            Log::warning('Direct message was saved but could not be broadcast live.', [
                'direct_message_id' => $message->id,
                'exception' => $exception,
            ]);
        }
    }
}
