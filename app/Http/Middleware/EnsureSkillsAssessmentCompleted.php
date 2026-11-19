<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSkillsAssessmentCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole(Role::Youth) && ! $user->youthProfile?->hasCompletedSkillsAssessment()) {
            if ($request->expectsJson() || $request->routeIs('mobile.*')) {
                return response()->json([
                    'code' => 'skills_assessment_required',
                    'message' => 'Complete your skills assessment to unlock personalized AI guidance.',
                    'assessment_url' => route('mobile.assessment.index'),
                ], 403);
            }

            return redirect()->route('youth.assessment.index')
                ->with('status', 'Complete your skills assessment to unlock personalized AI guidance.');
        }

        return $next($request);
    }
}
