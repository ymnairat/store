<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get user's teams
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        // If user has teams, filter warehouses by user teams
        if (count($userTeams) > 0) {
            $warehouses = Warehouse::whereHas('teams', function($query) use ($userTeams) {
                $query->whereIn('teams.id', $userTeams);
            })->get();
        } else {
            // If user has no teams (admin), show all warehouses
            $warehouses = Warehouse::all();
        }
        
        return response()->json($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager' => 'nullable|string|max:255',
            'manager_location' => 'nullable|string|max:255',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $warehouse = Warehouse::create($validated);
        
        // Sync teams if provided
        if (isset($validated['team_ids']) && is_array($validated['team_ids'])) {
            $warehouse->teams()->sync($validated['team_ids']);
        }
        
        $warehouse->load('teams');
        return response()->json($warehouse, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $warehouse = Warehouse::with('teams')->findOrFail($id);
        
        // Check if user has access to this warehouse
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        
        if (count($userTeams) > 0) {
            $warehouseTeams = $warehouse->teams()->pluck('teams.id')->toArray();
            $hasAccess = count(array_intersect($userTeams, $warehouseTeams)) > 0;
            
            if (!$hasAccess) {
                return response()->json(['error' => 'غير مصرح لك بالوصول إلى هذا المخزن'], 403);
            }
        }
        
        return response()->json($warehouse);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $warehouse = Warehouse::findOrFail($id);
        
        // Check access
        $userTeams = $user->teams()->pluck('teams.id')->toArray();
        if (count($userTeams) > 0) {
            $warehouseTeams = $warehouse->teams()->pluck('teams.id')->toArray();
            $hasAccess = count(array_intersect($userTeams, $warehouseTeams)) > 0;
            
            if (!$hasAccess) {
                return response()->json(['error' => 'غير مصرح لك بالوصول إلى هذا المخزن'], 403);
            }
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'manager' => 'nullable|string|max:255',
            'manager_location' => 'nullable|string|max:255',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $warehouse->update($validated);
        
        // Sync teams if provided
        if (array_key_exists('team_ids', $validated)) {
            $warehouse->teams()->sync($validated['team_ids'] ?? []);
        }
        
        $warehouse->load('teams');
        return response()->json($warehouse);
    }

    public function destroy(string $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return response()->json(['success' => true]);
    }
}
