<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return User::select(['id', 'username', 'email', 'is_active', 'created_at'])
            ->latest()
            ->paginate(20);
    }
}
