<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $data = [
            'notifications' => $request->user()->notifications()->paginate(20),
        ];

        return $request->routeIs('mobile.*') ? response()->json($data) : view('notifications.index', $data);
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

        if ($request->routeIs('mobile.*')) {
            return response()->json(['message' => 'Notifications marked as read.']);
        }

        return back();
    }
}
