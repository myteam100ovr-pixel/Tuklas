<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use Illuminate\View\View;

class CareerPathCatalogController extends Controller
{
    public function __invoke(): View
    {
        return view('careers.index', [
            'careers' => CareerPath::published()->orderBy('title')->paginate(12),
        ]);
    }
}
