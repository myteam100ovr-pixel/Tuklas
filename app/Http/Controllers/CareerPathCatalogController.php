<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use App\Services\CareerPathSkillMatcher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerPathCatalogController extends Controller
{
    public function __invoke(Request $request, CareerPathSkillMatcher $matcher): View
    {
        $careers = CareerPath::published()->orderBy('title')->paginate(12);
        $profile = $request->user()->youthProfile;
        $careers->getCollection()->each(fn (CareerPath $careerPath) => $careerPath->setAttribute('skill_match', $matcher->match($careerPath, $profile)));

        return view('careers.index', [
            'careers' => $careers,
        ]);
    }
}
