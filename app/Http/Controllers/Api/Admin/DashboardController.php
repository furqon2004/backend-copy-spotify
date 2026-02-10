<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'stats' => [
                'users_count' => User::count(),
                'artists_count' => Artist::count(),
                'total_streams' => Song::sum('stream_count'),
                'active_sessions' => DB::table('sessions')->count(),
            ],
            'latest_reports' => DB::table('content_reports')->latest()->limit(5)->get()
        ]);
    }
}