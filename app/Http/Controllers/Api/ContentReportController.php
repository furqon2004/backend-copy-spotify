<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContentReport::with('reporter:id,username')
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_type' => 'required|in:SONG,PLAYLIST,USER',
            'target_id' => 'required|uuid',
            'reason' => 'required|string|min:10',
        ]);

        $report = ContentReport::create(array_merge($data, [
            'reporter_id' => auth()->id(),
        ]));

        return response()->json($report, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = ContentReport::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:PENDING,RESOLVED,REJECTED',
        ]);

        $report->update($data);

        return response()->json($report);
    }

    public function destroy(int $id): JsonResponse
    {
        ContentReport::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
