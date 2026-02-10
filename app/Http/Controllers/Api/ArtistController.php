<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ArtistController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'artist_name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'avatar' => 'required|image|max:5120',
        ]);

        return DB::transaction(function () use ($request) {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
            ]);

            $avatarUrl = Cloudinary::upload($request->file('avatar')->getRealPath(), [
                'folder' => 'spotify_clone/artists',
                'transformation' => [
                    'width' => 500, 'height' => 500, 'crop' => 'fill', 'gravity' => 'face'
                ]
            ])->getSecurePath();

            $artist = Artist::create([
                'user_id' => $user->id,
                'name' => $request->artist_name,
                'slug' => str($request->artist_name)->slug(),
                'bio' => $request->bio,
                'avatar_url' => $avatarUrl,
                'is_verified' => false
            ]);

            $token = $user->createToken('access_token', ['*'], now()->addMonth())->plainTextToken;

            return response()->json([
                'message' => 'Artist account created successfully',
                'access_token' => $token,
                'artist' => $artist,
            ], 201);
        });
    }
}