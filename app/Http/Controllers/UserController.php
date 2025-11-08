<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.edit')->only(['edit', 'update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with(['roles', 'teams'])->get();
        $roles = Role::all();
        $teams = Team::all();
        
        return view('users.index', compact('users', 'roles', 'teams'));
    }

    public function create()
    {
        $roles = Role::all();
        $teams = Team::all();
        return view('users.form', compact('roles', 'teams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['password_confirmation']);
        
        $user = User::create($validated);
        
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }
        
        if ($request->has('team_ids')) {
            $user->teams()->sync($request->team_ids);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المستخدم بنجاح',
                'user' => $user->load(['roles', 'teams'])
            ]);
        }

        return redirect()->route('users.index')->with('success', 'تم إضافة المستخدم بنجاح');
    }

    public function show(User $user)
    {
        $user->load(['roles', 'teams']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $teams = Team::all();
        $user->load(['roles', 'teams']);
        return view('users.form', compact('user', 'roles', 'teams'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);
        
        $user->update($validated);
        
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        } else {
            $user->roles()->detach();
        }
        
        if ($request->has('team_ids')) {
            $user->teams()->sync($request->team_ids);
        } else {
            $user->teams()->detach();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المستخدم بنجاح',
                'user' => $user->load(['roles', 'teams'])
            ]);
        }

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم بنجاح');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'لا يمكن حذف نفسك'], 400);
            }
            return back()->with('error', 'لا يمكن حذف نفسك');
        }

        $user->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح'
            ]);
        }

        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم بنجاح');
    }
}

