<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StreamHistory;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StreamController extends Controller
{
    public function log(Request $request): JsonResponse
    {
        $request->validate([
            'song_id' => 'required|uuid',
            'duration_played_ms' => 'required|integer|min:30000',
            'source' => 'required|in:PLAYLIST,SEARCH,AI_RECOMMENDATION'
        ]);

        DB::transaction(function () use ($request) {
            StreamHistory::create([
                'user_id' => auth()->id(),
                'song_id' => $request->song_id,
                'duration_played_ms' => $request->duration_played_ms,
                'source' => $request->source,
                'device' => $request->header('User-Agent'),
                'played_at' => now()
            ]);

            Song::where('id', $request->song_id)->increment('stream_count');
        });

        return response()->json(['status' => 'success'], 201);
    }
}
