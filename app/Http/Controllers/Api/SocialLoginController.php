<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * Provider yang diizinkan
     */
    private array $allowedProviders = ['google'];

    /**
     * Redirect ke halaman OAuth provider.
     *
     * Frontend memanggil endpoint ini → backend return redirect URL →
     * frontend bisa redirect user ke URL tersebut.
     */
    public function redirect(string $provider): JsonResponse
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json([
                'message' => "Provider '{$provider}' tidak didukung. Gunakan: " . implode(', ', $this->allowedProviders)
            ], 422);
        }

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * Handle callback dari OAuth provider.
     *
     * Menerima `code` dari provider, exchange ke user info,
     * lalu create/find user dan return Sanctum tokens.
     */
    public function callback(Request $request, string $provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return redirect(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'))
                . '/auth/callback?error=' . urlencode("Provider '{$provider}' tidak didukung."));
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return redirect(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'))
                . '/auth/callback?error=' . urlencode('Gagal autentikasi dengan ' . ucfirst($provider) . ': ' . $e->getMessage()));
        }

        return DB::transaction(function () use ($socialUser, $provider) {
            // 1. Cari user yang sudah linked dengan provider ini
            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$user) {
                // 2. Cari user by email (mungkin sudah register manual)
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    // Link social provider ke user existing
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'profile_image_url' => $user->profile_image_url ?? $socialUser->getAvatar(),
                    ]);
                } else {
                    // 3. Create user baru
                    $username = $this->generateUniqueUsername($socialUser->getName() ?? $socialUser->getEmail());

                    $user = User::create([
                        'email' => $socialUser->getEmail(),
                        'username' => $username,
                        'full_name' => $socialUser->getName(),
                        'profile_image_url' => $socialUser->getAvatar(),
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'password_hash' => null, // Social login, no password
                    ]);
                }
            }

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Hapus token lama
            $user->tokens()->delete();

            // Issue tokens
            $expiration = $this->getExpirationByRole($user);
            $accessToken = $user->createToken('access_token', ['*'], $expiration)->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['issue-access-token'], now()->addMonths(6))->plainTextToken;

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

            $params = http_build_query([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiration->toDateTimeString(),
                'role' => $this->getUserRole($user),
                'is_new_user' => $user->wasRecentlyCreated ? '1' : '0',
            ]);

            return redirect("{$frontendUrl}/auth/callback?{$params}");
        });
    }

    /**
     * Generate username unik dari nama
     */
    private function generateUniqueUsername(string $name): string
    {
        $base = Str::slug($name, '');
        if (empty($base)) {
            $base = 'user';
        }

        $username = $base . rand(1000, 9999);
        while (User::where('username', $username)->exists()) {
            $username = $base . rand(1000, 9999);
        }

        return $username;
    }

    private function getExpirationByRole($user)
    {
        if ($user->admin) return now()->addDay();
        if ($user->artist) return now()->addMonth();
        return now()->addMonths(2);
    }

    private function getUserRole($user): string
    {
        if ($user->admin) return 'ADMIN';
        if ($user->artist) return 'ARTIST';
        return 'USER';
    }
}
