<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'Username' => 'required|string',
            'password' => 'required'
        ]);

        $user = User::with(['admin', 'artist'])
            ->where(function ($query) use ($request) {
                $query->where('email', $request->login)
                    ->orWhere('username', $request->login);
            })->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages(['username' => ['Kredensial salah.']]);
        }

        return DB::transaction(function () use ($user) {
            // Hapus semua token lama agar database tidak bengkak (Optimal untuk data besar)
            $user->tokens()->delete();

            $expiration = $this->getExpirationByRole($user);

            // Access Token: Digunakan untuk request data API
            $accessToken = $user->createToken('access_token', ['*'], $expiration)->plainTextToken;

            // Refresh Token: Hanya punya kemampuan untuk minta access token baru
            $refreshToken = $user->createToken('refresh_token', ['issue-access-token'], now()->addMonths(6))->plainTextToken;

            $user->update(['last_login_at' => now()]);

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiration->toDateTimeString(),
                'role' => $this->getUserRole($user),
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email
                ]
            ]);
        });
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'full_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:Man,Woman,Non-binary,Something else,Prefer not to say',
        ]);

        if (!$request->email && !$request->phone_number) {
            throw ValidationException::withMessages(['email' => ['Email or Phone Number is required.']]);
        }

        return DB::transaction(function () use ($request) {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email, // Can be null if using phone
                'phone_number' => $request->phone_number,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
            ]);

            $expiration = $this->getExpirationByRole($user);
            $accessToken = $user->createToken('access_token', ['*'], $expiration)->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['issue-access-token'], now()->addMonths(6))->plainTextToken;

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiration->toDateTimeString(),
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ]
            ], 201);
        });
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->tokenCan('issue-access-token')) {
            return response()->json(['message' => 'Invalid refresh token'], 403);
        }

        return DB::transaction(function () use ($user) {
            $user->tokens()->where('name', 'access_token')->delete();

            $expiration = $this->getExpirationByRole($user);
            $newAccessToken = $user->createToken('access_token', ['*'], $expiration)->plainTextToken;

            return response()->json([
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiration->toDateTimeString(),
            ]);
        });
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Helper: Menentukan durasi token berdasarkan Role
     */
    private function getExpirationByRole($user)
    {
        if ($user->admin)
            return now()->addDay();
        if ($user->artist)
            return now()->addMonth();
        return now()->addMonths(2);
    }

    /**
     * Helper: Mengambil label role user
     */
    private function getUserRole($user): string
    {
        if ($user->admin)
            return 'ADMIN';
        if ($user->artist)
            return 'ARTIST';
        return 'USER';
    }
}