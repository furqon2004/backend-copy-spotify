<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ArtistController extends Controller
{
    /**
     * Register a new artist account (public, no auth required).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'artist_name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|max:5120',
        ]);

        return DB::transaction(function () use ($request) {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
            ]);

            $avatarUrl = null;
            if ($request->hasFile('avatar')) {
                $avatarUrl = Cloudinary::upload($request->file('avatar')->getRealPath(), [
                    'folder' => 'spotify_clone/artists',
                    'transformation' => [
                        'width' => 500, 'height' => 500, 'crop' => 'fill', 'gravity' => 'face'
                    ]
                ])->getSecurePath();
            }

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

    /**
     * View artist public profile by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $artist = Artist::where('slug', $slug)
            ->with([
                'songs' => function ($q) {
                    $q->where('status', 'APPROVED')
                        ->select(['id', 'artist_id', 'album_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count'])
                        ->with('album:id,title,cover_image_url')
                        ->orderBy('stream_count', 'desc')
                        ->limit(20);
                },
                'albums' => function ($q) {
                    $q->select(['id', 'artist_id', 'title', 'cover_image_url', 'release_date', 'type'])
                        ->withCount('songs')
                        ->latest('release_date');
                },
            ])
            ->firstOrFail();

        return response()->json($artist);
    }

    /**
     * Upgrade an existing logged-in user to artist.
     */
    public function upgrade(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->artist) {
            return response()->json(['message' => 'You are already registered as an artist.'], 409);
        }

        $request->validate([
            'artist_name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $avatarUrl = Cloudinary::upload($request->file('avatar')->getRealPath(), [
                'folder' => 'spotify_clone/artists',
                'transformation' => [
                    'width' => 500, 'height' => 500, 'crop' => 'fill', 'gravity' => 'face'
                ]
            ])->getSecurePath();
        }

        $artist = Artist::create([
            'user_id' => $user->id,
            'name' => $request->artist_name,
            'slug' => str($request->artist_name)->slug(),
            'bio' => $request->bio,
            'avatar_url' => $avatarUrl,
            'is_verified' => false,
        ]);

        return response()->json([
            'message' => 'Successfully upgraded to artist.',
            'artist' => $artist,
        ], 201);
    }
}