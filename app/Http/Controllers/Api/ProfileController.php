<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function show()
    {
        $user = auth()->user();

        // 1. Public Playlists
        $publicPlaylists = \App\Models\Playlist::where('user_id', $user->id)
            ->where('is_public', true)
            ->latest()
            ->get();

        // 2. Top Tracks (from StreamHistory)
        // Group by song_id, count plays, order by count desc
        $topTracks = \App\Models\StreamHistory::where('user_id', $user->id)
            ->where('played_at', '>=', now()->subDays(30)) // Last 30 days
            ->select('song_id', \Illuminate\Support\Facades\DB::raw('count(*) as play_count'))
            ->groupBy('song_id')
            ->orderByDesc('play_count')
            ->limit(5)
            ->with(['song.artist', 'song.album']) // Eager load relations
            ->get()
            ->map(function ($history) {
                return $history->song;
            });

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'profile_image_url' => $user->profile_image_url,
            'phone_number' => $user->phone_number,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'followers_count' => 0, // Placeholder
            'following_count' => 0, // Placeholder
            'public_playlists' => $publicPlaylists,
            'top_tracks' => $topTracks
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->only(['full_name', 'date_of_birth', 'gender', 'phone_number']);

        // Handle profile image upload via Cloudinary
        if ($request->hasFile('profile_image')) {
            $request->validate([
                'profile_image' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            // Delete old image if exists
            if ($user->profile_image_url) {
                $publicId = $this->cloudinary->getPublicIdFromUrl($user->profile_image_url);
                if ($publicId) {
                    $this->cloudinary->delete($publicId);
                }
            }

            $data['profile_image_url'] = $this->cloudinary->uploadImage(
                $request->file('profile_image'),
                'avatars/users'
            );
        }

        $user->update($data);

        return response()->json($user);
    }
}
