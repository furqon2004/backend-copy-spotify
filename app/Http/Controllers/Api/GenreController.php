<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Genre::select(['id', 'name', 'slug'])->get());
    }

    public function songs($slug)
    {
        return Genre::where('slug', $slug)->firstOrFail()
            ->songs()
            ->select(['songs.id', 'songs.title', 'songs.file_path', 'songs.cover_url', 'songs.artist_id'])
            ->with('artist:id,name')
            ->paginate(30);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name',
        ]);

        $genre = Genre::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return response()->json($genre, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $genre = Genre::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name,' . $id,
        ]);

        $genre->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return response()->json($genre);
    }

    public function destroy(int $id): JsonResponse
    {
        Genre::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}