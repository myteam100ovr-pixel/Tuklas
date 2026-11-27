<?php

namespace App\Http\Controllers;

use App\Support\SupportedLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class AccountLocaleController extends Controller
{
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(SupportedLocale::VALUES)],
        ]);
        $user = $request->user();
        $user->forceFill(['locale' => $validated['locale']])->save();
        App::setLocale($user->preferredLocale());
        $message = __('api.locale.updated');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'locale' => $user->preferredLocale(),
            ]);
        }

        return back()->with('status', $message);
    }
}
