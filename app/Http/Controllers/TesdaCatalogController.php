<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgram;
use Illuminate\View\View;

class TesdaCatalogController extends Controller
{
    public function index(): View
    {
        $programs = TrainingProgram::published()
            ->orderBy('title')
            ->paginate(12);

        return view('tesda.index', ['programs' => $programs]);
    }
}
