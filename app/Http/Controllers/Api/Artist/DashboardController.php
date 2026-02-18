<?php

namespace App\Http\Controllers\Api\Artist;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\StreamHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $artist = auth()->user()->artist;

        if (!$artist) {
            return response()->json(['message' => 'You are not registered as an artist'], 403);
        }

        $artistId = $artist->id;
        $songIds = Song::where('artist_id', $artistId)->pluck('id');

        // Basic stats
        $stats = [
            'monthly_listeners' => $artist->monthly_listeners,
            'total_plays' => Song::where('artist_id', $artistId)->sum('stream_count'),
            'total_songs' => Song::where('artist_id', $artistId)->count(),
            'streams_today' => StreamHistory::whereIn('song_id', $songIds)->whereDate('played_at', today())->count(),
            'streams_this_month' => StreamHistory::whereIn('song_id', $songIds)
                ->where('played_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        // Performance chart — daily (last 7 days)
        $dailyChart = StreamHistory::whereIn('song_id', $songIds)
            ->where('played_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(played_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Performance chart — monthly (last 12 months)
        $monthlyChart = StreamHistory::whereIn('song_id', $songIds)
            ->where('played_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('YEAR(played_at) as year'),
                DB::raw('MONTH(played_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Performance chart — yearly (all time grouped by year)
        $yearlyChart = StreamHistory::whereIn('song_id', $songIds)
            ->select(
                DB::raw('YEAR(played_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        // Top songs
        $topSongs = Song::where('artist_id', $artistId)
            ->select(['id', 'title', 'cover_url', 'stream_count', 'duration_seconds'])
            ->orderBy('stream_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'daily_chart' => $dailyChart,
            'monthly_chart' => $monthlyChart,
            'yearly_chart' => $yearlyChart,
            'top_songs' => $topSongs,
        ]);
    }
}
