<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'max:55'],
            'email' => ['email', 'required', 'unique:users'],
            'password' => ['confirmed', Rules\Password::defaults()],
            'image' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048']
        ]);

        $validatedData['password'] = bcrypt($request->password);

        $user = User::create($validatedData);

        $token = $user->createToken('authToken');

        return response(
            [
                'user' => $user,
                'access_token' => $token->plainTextToken,
                'message' => 'Account created successfully',
            ],
            200
        );
    }

    // Login
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => ['email', 'required'],
            'password' => ['required', 'min:8'],
        ]);

        if (!Auth::attempt($validatedData)) {
            return response(['message' => 'Invalid credentials'], 401);
        }
        $user = Auth::user();
        return response([
            'user' => $user,
            'token' => $user->createToken('authToken')->plainTextToken,
            'message' => 'Logged in successfully!',
        ], 200);
    }
    

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response(['message' => 'Logged out!'], 200);
    }
}
