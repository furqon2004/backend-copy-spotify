<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        return Genre::select(['id', 'name', 'slug'])->get();
    }

    public function songs($slug)
    {
        return Genre::where('slug', $slug)->firstOrFail()
            ->songs()
            ->select(['songs.id', 'songs.title', 'songs.file_url', 'songs.artist_id'])
            ->with('artist:id,name')
            ->paginate(30);
    }
}