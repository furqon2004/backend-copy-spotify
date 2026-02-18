<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List all users with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::select(['id', 'username', 'email', 'full_name', 'profile_image_url', 'is_active', 'last_login_at', 'created_at']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Show user detail.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::with(['admin', 'artist:id,user_id,name,slug,is_verified'])
            ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Toggle user active status (ban/unban).
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'User activated.' : 'User banned.',
            'user' => $user->only(['id', 'username', 'email', 'is_active']),
        ]);
    }
}
