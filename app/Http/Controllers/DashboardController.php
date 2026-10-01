<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        return view('admin.dashboard');
    }

    public function trainer(): View
    {
        return view('trainer.dashboard');
    }

    public function youth(): View
    {
        return view('youth.dashboard');
    }
}
