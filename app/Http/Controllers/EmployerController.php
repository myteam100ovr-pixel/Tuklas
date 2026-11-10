<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $employers = Employer::query()->orderBy('name')->paginate(20);

        return $request->routeIs('mobile.*') ? response()->json(['employers' => $employers]) : view('operations.employers', compact('employers'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Employer::create($this->validatedData($request));

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Employer saved.'], 201) : back()->with('status', 'Employer saved.');
    }

    public function update(Request $request, Employer $employer): RedirectResponse|JsonResponse
    {
        $employer->update($this->validatedData($request));

        return $request->routeIs('mobile.*') ? response()->json(['message' => 'Employer updated.']) : back()->with('status', 'Employer updated.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'is_verified' => ['required', 'boolean'],
        ]);
    }
}
