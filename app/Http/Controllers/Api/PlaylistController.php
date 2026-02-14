<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlaylistController extends Controller
{
    public function index(): JsonResponse
    {
        $playlists = Playlist::where('user_id', auth()->id())
            ->select(['id', 'name', 'cover_url', 'is_public'])
            ->latest()
            ->paginate(20);

        return response()->json($playlists);
    }

    public function show(string $id): JsonResponse
    {
        $playlist = Playlist::with(['songs' => function ($q) {
            $q->select(['songs.id', 'songs.title', 'songs.cover_url', 'songs.duration_seconds', 'songs.artist_id'])
                ->with('artist:id,name')
                ->orderBy('playlist_items.position');
        }])->findOrFail($id);

        return response()->json($playlist);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|string|max:2048',
            'is_ai_generated' => 'boolean',
            'ai_prompt_used' => 'nullable|string',
            'is_public' => 'boolean'
        ]);

        $playlist = Playlist::create(array_merge($data, ['user_id' => auth()->id()]));
        return response()->json($playlist, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $playlist = Playlist::where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|string|max:2048',
            'is_public' => 'boolean'
        ]);

        $playlist->update($data);
        return response()->json($playlist);
    }

    public function destroy(string $id): JsonResponse
    {
        $playlist = Playlist::where('user_id', auth()->id())->findOrFail($id);
        $playlist->delete();

        return response()->json(null, 204);
    }

    public function addSong(Request $request, string $id): JsonResponse
    {
        $request->validate(['song_id' => 'required|uuid|exists:songs,id']);

        $lastPosition = PlaylistItem::where('playlist_id', $id)->max('position') ?? 0;

        $item = PlaylistItem::create([
            'playlist_id' => $id,
            'song_id' => $request->song_id,
            'position' => $lastPosition + 1
        ]);

        return response()->json($item, 201);
    }

    public function removeSong(string $id, string $songId): JsonResponse
    {
        $deleted = PlaylistItem::where('playlist_id', $id)
            ->where('song_id', $songId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Song not found in playlist'], 404);
        }

        return response()->json(['message' => 'Song removed from playlist']);
    }

    public function reorder(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:playlist_items,id',
            'items.*.position' => 'required|integer'
        ]);

        $items = $request->items;
        $ids = array_column($items, 'id');

        $cases = [];
        $params = [];

        foreach ($items as $item) {
            $cases[] = "WHEN id = ? THEN ?";
            $params[] = $item['id'];
            $params[] = $item['position'];
        }

        $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));
        $rawQuery = "UPDATE playlist_items SET position = CASE " . implode(' ', $cases) . " END WHERE id IN ($idsPlaceholder)
AND playlist_id = ?";

        $finalParams = array_merge($params, $ids, [$id]);

        DB::transaction(function () use ($rawQuery, $finalParams) {
            DB::update($rawQuery, $finalParams);
        });

        return response()->json(['message' => 'Playlist order synchronized']);
    }
}