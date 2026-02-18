<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    /**
     * List all songs with filters for admin moderation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Song::with(['artist:id,name,slug', 'album:id,title', 'genres:id,name'])
            ->select(['id', 'artist_id', 'album_id', 'title', 'slug', 'status', 'stream_count', 'created_at']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('artist_id')) {
            $query->where('artist_id', $request->artist_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhereHas('artist', fn($aq) => $aq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $songs = $query->latest()->paginate(20);

        return response()->json($songs);
    }

    /**
     * Approve a pending song.
     */
    public function approve(string $id): JsonResponse
    {
        $song = Song::findOrFail($id);
        $song->update(['status' => 'APPROVED']);

        return response()->json([
            'message' => 'Song approved successfully.',
            'song' => $song,
        ]);
    }

    /**
     * Reject a pending song.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $song = Song::findOrFail($id);
        $song->update(['status' => 'REJECTED']);

        return response()->json([
            'message' => 'Song rejected.',
            'reason' => $request->reason,
            'song' => $song,
        ]);
    }

    /**
     * Force delete an illegal/violating song.
     */
    public function destroy(string $id): JsonResponse
    {
        $song = Song::findOrFail($id);
        $song->delete();

        return response()->json(['message' => 'Song deleted successfully.'], 204);
    }
}
