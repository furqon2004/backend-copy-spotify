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

public function index(): JsonResponse
{
$playlists = Playlist::where('user_id', auth()->id())
->select(['id', 'name', 'cover_url', 'is_public'])
->latest()
->paginate(20);

return response()->json($playlists);
}

public function store(Request $request): JsonResponse
{
$data = $request->validate([
'name' => 'required|string|max:255',
'is_public' => 'boolean'
]);

$playlist = Playlist::create(array_merge($data, ['user_id' => auth()->id()]));
return response()->json($playlist, 201);
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
}