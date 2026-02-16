<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StreamLogRequest;
use App\Http\Requests\StreamAudioRequest;
use App\Models\StreamHistory;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class StreamController extends Controller
{
    /**
     * Log a stream event for a song.
     */
    public function log(StreamLogRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            StreamHistory::create([
                'user_id' => auth()->id(),
                'song_id' => $request->song_id,
                'duration_played_ms' => $request->duration_played_ms,
                'source' => $request->source,
                'device' => Str::limit($request->header('User-Agent'), 255, ''),
                'played_at' => now()
            ]);

            Song::where('id', $request->song_id)->increment('stream_count');
        });

        return response()->json(['status' => 'success'], 201);
    }

    /**
     * Generate a temporary signed URL for streaming a song.
     */
    public function getSecureLink(string $id): JsonResponse
    {
        $song = Song::select(['id', 'title'])->findOrFail($id);

        $url = URL::temporarySignedRoute(
            'stream.audio',
            now()->addMinutes(15),
            ['id' => $song->id]
        );

        return response()->json([
            'stream_url' => $url,
            'expires_in' => 900, // 15 minutes in seconds
        ]);
    }

    /**
     * Stream the audio file via the signed URL.
     */
    public function streamAudio(StreamAudioRequest $request, string $id)
    {
        $song = Song::select(['id', 'file_path'])->findOrFail($id);

        if (!$song->file_path) {
            return response()->json(['message' => 'Audio file not available.'], 404);
        }

        // If file_path is a full URL (e.g. Cloudinary), redirect to it
        if (filter_var($song->file_path, FILTER_VALIDATE_URL)) {
            return response()->json([
                'stream_url' => $song->file_path,
            ]);
        }

        // Otherwise stream from local storage
        $path = storage_path('app/' . $song->file_path);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Audio file not found.'], 404);
        }

        return response()->stream(function () use ($path) {
            $stream = fopen($path, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => filesize($path),
        ]);
    }
}
