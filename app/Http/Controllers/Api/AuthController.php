<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (isset($validated['role_id'])) {
            $user->assignRole($validated['role_id']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles.permissions'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $user = User::with('roles.permissions')->where(function($query) use ($request) {
                $query->where('username', $request->username)
                      ->orWhere('email', $request->username);
            })->first();
        } catch (\Exception $e) {
            Log::error('Login: Error loading user with relationships', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // If eager loading fails, try without relationships
            try {
                $user = User::where(function($query) use ($request) {
                    $query->where('username', $request->username)
                          ->orWhere('email', $request->username);
                })->first();
            } catch (\Exception $e2) {
                Log::error('Login: Error loading user', [
                    'error' => $e2->getMessage(),
                    'trace' => $e2->getTraceAsString()
                ]);
                return response()->json([
                    'message' => 'حدث خطأ في الخادم',
                    'error' => 'Server error occurred'
                ], 500);
            }
        }

        if (!$user) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
                'errors' => [
                    'username' => ['اسم المستخدم أو البريد الإلكتروني غير موجود']
                ]
            ], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة',
                'errors' => [
                    'password' => ['كلمة المرور غير صحيحة']
                ]
            ], 422);
        }

        try {
            $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Exception $e) {
            Log::error('Login: Error creating token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'حدث خطأ في إنشاء رمز الوصول',
                'error' => 'Token creation failed'
            ], 500);
        }

        // Try to load relationships if not already loaded
        if (!$user->relationLoaded('roles')) {
            try {
                $user->load('roles.permissions');
            } catch (\Exception $e) {
                Log::warning('Login: Could not load roles.permissions', [
                    'error' => $e->getMessage()
                ]);
                // Silently fail if relationships can't be loaded
                try {
                    $user->load('roles');
                } catch (\Exception $e2) {
                    Log::warning('Login: Could not load roles', [
                        'error' => $e2->getMessage()
                    ]);
                    // Continue without roles
                }
            }
        }

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load(['roles.permissions', 'teams']));
    }
}
