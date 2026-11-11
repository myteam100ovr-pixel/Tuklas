<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipApplicationController extends Controller
{
    public function store(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Youth), 403);
        abort_unless(Scholarship::published()->whereKey($scholarship->id)->exists(), 404);
        abort_if(
            $scholarship->applications()->where('user_id', $request->user()->id)->exists(),
            422,
            'You have already applied to this scholarship.',
        );

        $data = $request->validate([
            'application_message' => ['required', 'string', 'min:20', 'max:3000'],
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $documents = collect($request->file('documents', []))->map(fn ($file): array => [
            'name' => basename($file->getClientOriginalName()),
            'path' => $file->store('scholarship-applications/'.$request->user()->id, 'local'),
        ])->all();

        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => $scholarship->id,
            'user_id' => $request->user()->id,
            'application_message' => $data['application_message'],
            'documents' => $documents,
            'status' => 'submitted',
        ]);

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => 'Scholarship application submitted.', 'application' => $application], 201)
            : back()->with('status', 'Scholarship application submitted.');
    }

    public function update(Request $request, ScholarshipApplication $application): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin, Role::TesdaAdmin), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['reviewing', 'approved', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $application->update($data + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $request->routeIs('mobile.*')
            ? response()->json(['message' => 'Scholarship application reviewed.'])
            : back()->with('status', 'Scholarship application reviewed.');
    }

    public function download(Request $request, ScholarshipApplication $application, int $document): StreamedResponse
    {
        $user = $request->user();
        abort_unless(
            $user->id === $application->user_id || $user->hasRole(Role::Admin, Role::TesdaAdmin),
            403,
        );
        $file = $application->documents[$document] ?? null;
        abort_unless(is_array($file) && isset($file['path'], $file['name']), 404);
        abort_unless(Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], basename($file['name']));
    }
}
