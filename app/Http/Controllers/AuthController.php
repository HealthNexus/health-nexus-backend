<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    // all users
    public function index()
    {
        if (auth()->user()->role->slug === 'admin') {
            $patients = User::latest()->get();
            return response([
                'patients' => $patients
            ], 200);
        } else if (auth()->user()->role->slug === 'doctor') {
            $patients = User::where('hospital_id', auth()->user()->hospital_id)->latest()->get();
            return response([
                'patients' => $patients
            ], 200);
        } else {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }
    }

    // Register
    private $patientRole = 5;
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'max:55'],
            'email' => ['email', 'required', 'unique:users'],
            'password' => ['confirmed', Rules\Password::defaults()],
            'avatar' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'hospital_id' => ['required', 'integer'],
        ]);

        $validatedData['password'] = bcrypt($request->password);

        //set role_id to 5 which is the default role for patients
        $validatedData['role_id'] = $this->patientRole;

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
        return response(['message' => 'Logged out successfully!'], 200);
    }
}
