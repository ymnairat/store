<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with(['roles.permissions', 'teams'])->get();
        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        if (isset($validated['team_ids'])) {
            $user->teams()->sync($validated['team_ids']);
        }

        return response()->json($user->load(['roles.permissions', 'teams']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with(['roles.permissions', 'teams'])->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (array_key_exists('roles', $validated)) {
            $user->roles()->sync($validated['roles'] ?? []);
        }

        if (array_key_exists('team_ids', $validated)) {
            $user->teams()->sync($validated['team_ids'] ?? []);
        }

        return response()->json($user->load(['roles.permissions', 'teams']));
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'لا يمكنك حذف حسابك الخاص'], 403);
        }

        $user->delete();
        return response()->json(['success' => true]);
    }
}
