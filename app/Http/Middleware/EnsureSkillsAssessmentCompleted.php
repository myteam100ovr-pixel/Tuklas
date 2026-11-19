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
        $assessmentRoutes = [
            'youth.assessment.index',
            'youth.assessment.store',
            'mobile.assessment.index',
            'mobile.assessment.store',
            'mobile.me',
            'mobile.auth.token.destroy',
        ];

        if ($user?->hasRole(Role::Youth)
            && ! $user->youthProfile?->hasCompletedSkillsAssessment()
            && ! $request->routeIs(...$assessmentRoutes)) {
            if ($request->expectsJson() || $request->routeIs('mobile.*')) {
                return response()->json([
                    'code' => 'skills_assessment_required',
                    'message' => 'Complete your skills assessment before using the workspace. Your answers unlock jobs, training, and personalized guidance.',
                    'assessment_url' => route('mobile.assessment.index'),
                ], 403);
            }

            return redirect()->route('youth.assessment.index')
                ->with('status', 'Complete your skills assessment first. Your answers unlock the workspace, jobs, training, and personalized guidance.');
        }

        return $next($request);
    }
}
