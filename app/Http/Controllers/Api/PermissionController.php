<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        return response()->json($permissions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create($validated);
        return response()->json($permission, 201);
    }

    public function show(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:permissions,name,' . $id,
            'display_name' => 'sometimes|string|max:255',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);
        return response()->json($permission);
    }

    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return response()->json(['success' => true]);
    }
}
