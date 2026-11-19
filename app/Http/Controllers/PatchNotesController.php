<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatchNotesController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $patchNotes = collect(json_decode(file_get_contents(resource_path('patch-notes.json')), true, flags: JSON_THROW_ON_ERROR))
            ->sortByDesc('published_at')
            ->values()
            ->all();

        if ($request->routeIs('mobile.*') || $request->expectsJson()) {
            return response()->json(['patch_notes' => $patchNotes]);
        }

        return view('patch-notes.index', compact('patchNotes'));
    }
}
