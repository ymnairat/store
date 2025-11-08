<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $teams = Team::all();
        return response()->json($teams);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#3b82f6',
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($team, 201);
    }

    public function show(string $id): JsonResponse
    {
        $team = Team::findOrFail($id);
        return response()->json($team);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $team = Team::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        $team->update($validated);
        return response()->json($team);
    }

    public function destroy(string $id): JsonResponse
    {
        $team = Team::findOrFail($id);
        $team->delete();
        return response()->json(['success' => true]);
    }
}
