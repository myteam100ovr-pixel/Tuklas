<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'email_verified_at', 'created_at'])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.users.index', ['users' => $users]);
    }
}
