<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['create', 'store']);
        $this->middleware('permission:roles.edit')->only(['edit', 'update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        
        // Group permissions
        $groupedPermissions = $permissions->groupBy('group');
        
        return view('roles.index', compact('roles', 'permissions', 'groupedPermissions'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy('group');
        return view('roles.form', compact('permissions', 'groupedPermissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الدور بنجاح',
                'role' => $role->load('permissions')
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'تم إضافة الدور بنجاح');
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy('group');
        $role->load('permissions');
        return view('roles.form', compact('role', 'permissions', 'groupedPermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح',
                'role' => $role->load('permissions')
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'تم تحديث الدور بنجاح');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الدور بنجاح'
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'تم حذف الدور بنجاح');
    }
}

