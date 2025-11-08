<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Try to find user by username or email
        $user = User::where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->filled('remember'));
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'user' => $user->load('roles.permissions'),
                    'token' => $user->createToken('auth-token')->plainTextToken
                ]);
            }
            
            return redirect()->intended(route('dashboard'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'بيانات الدخول غير صحيحة'
            ], 401);
        }

        return back()->withErrors([
            'username' => 'بيانات الدخول غير صحيحة',
        ])->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('login');
    }
}

