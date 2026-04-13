<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Auto-create test admin if doesn't exist so frontend works instantly
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin', 'password' => Hash::make('secret')]
        );

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
