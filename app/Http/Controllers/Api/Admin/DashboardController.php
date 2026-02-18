<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Artist;
use App\Models\Song;
use App\Models\StreamHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Basic counts
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'total_artists' => Artist::count(),
                'total_songs' => Song::count(),
                'total_streams' => Song::sum('stream_count'),
                'pending_songs' => Song::where('status', 'PENDING')->count(),
            ];

            // Daily streams (today vs yesterday)
            $stats['streams_today'] = StreamHistory::whereDate('played_at', today())->count();
            $stats['streams_yesterday'] = StreamHistory::whereDate('played_at', today()->subDay())->count();

            // Trending songs (top 10 by streams in last 7 days)
            $trendingSongs = Song::select(['songs.id', 'songs.title', 'songs.artist_id', 'songs.cover_url', 'songs.stream_count'])
                ->join('stream_history', 'songs.id', '=', 'stream_history.song_id')
                ->where('stream_history.played_at', '>=', now()->subDays(7))
                ->with('artist:id,name,slug')
                ->groupBy('songs.id', 'songs.title', 'songs.artist_id', 'songs.cover_url', 'songs.stream_count')
                ->orderByRaw('COUNT(stream_history.id) DESC')
                ->limit(10)
                ->get()
                ->map(function ($song) {
                    $song->weekly_plays = StreamHistory::where('song_id', $song->id)
                        ->where('played_at', '>=', now()->subDays(7))
                        ->count();
                    return $song;
                });

            // Trending artists (top 10 by total streams in last 7 days)
            $trendingArtists = Artist::select(['artists.id', 'artists.name', 'artists.slug', 'artists.avatar_url', 'artists.monthly_listeners'])
                ->join('songs', 'artists.id', '=', 'songs.artist_id')
                ->join('stream_history', 'songs.id', '=', 'stream_history.song_id')
                ->where('stream_history.played_at', '>=', now()->subDays(7))
                ->groupBy('artists.id', 'artists.name', 'artists.slug', 'artists.avatar_url', 'artists.monthly_listeners')
                ->orderByRaw('COUNT(stream_history.id) DESC')
                ->limit(10)
                ->get()
                ->map(function ($artist) {
                    $artist->weekly_plays = DB::table('stream_history')
                        ->join('songs', 'stream_history.song_id', '=', 'songs.id')
                        ->where('songs.artist_id', $artist->id)
                        ->where('stream_history.played_at', '>=', now()->subDays(7))
                        ->count();
                    return $artist;
                });

            // Latest content reports
            $latestReports = DB::table('content_reports')
                ->join('users', 'content_reports.reporter_id', '=', 'users.id')
                ->select([
                    'content_reports.id',
                    'content_reports.target_type',
                    'content_reports.target_id',
                    'content_reports.reason',
                    'content_reports.status',
                    'content_reports.created_at',
                    'users.username as reporter_name',
                ])
                ->latest('content_reports.created_at')
                ->limit(10)
                ->get();

            return response()->json([
                'stats' => $stats,
                'trending_songs' => $trendingSongs,
                'trending_artists' => $trendingArtists,
                'latest_reports' => $latestReports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load dashboard',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}