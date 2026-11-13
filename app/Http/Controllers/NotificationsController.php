<?php

namespace App\Http\Controllers;

use App\Events\UserNotificationsChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $data = [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ];

        return $request->routeIs('mobile.*') || $request->expectsJson() ? response()->json($data) : view('notifications.index', $data);
    }

    public function read(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Notifications marked as read.']);
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        try {
            UserNotificationsChanged::dispatch($request->user()->id);
        } catch (\Throwable $exception) {
            Log::warning('Notifications were marked read but the live update could not be broadcast.', [
                'user_id' => $request->user()->id,
                'exception' => $exception,
            ]);
        }

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Notifications marked as read.']);
        }

        return back();
    }
}
