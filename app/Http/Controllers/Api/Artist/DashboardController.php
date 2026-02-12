<?php

namespace App\Http\Controllers\Api\Artist;

use App\Http\Controllers\Controller;
use App\Models\StreamHistory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $artist = auth()->user()->artist;

        if (!$artist) {
            return response()->json(['message' => 'You are not registered as an artist'], 403);
        }

        $artistId = $artist->id;

        return response()->json([
            'stats' => [
                'monthly_listeners' => $artist->monthly_listeners,
                'total_plays' => DB::table('songs')->where('artist_id', $artistId)->sum('stream_count'),
            ],
            'performance_chart' => StreamHistory::whereIn('song_id', function ($query) use ($artistId) {
                $query->select('id')->from('songs')->where('artist_id', $artistId);
            })
                ->where('played_at', '>=', now()->subDays(7))
                ->select(DB::raw('DATE(played_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->get()
        ]);
    }
}
