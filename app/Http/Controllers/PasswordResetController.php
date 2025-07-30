<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link to user's email
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email']
        ]);

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password reset link sent to your email address.',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to send password reset link. Please try again.',
        ], 422);
    }

    /**
     * Reset password using token
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Attempt to reset the user's password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Revoke all existing tokens for security
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password has been reset successfully.',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => $this->getResetErrorMessage($status),
        ], 422);
    }

    /**
     * Verify if reset token is valid
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email address.',
            ], 422);
        }

        // Check if token is valid
        if (!Password::tokenExists($user, $request->token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired reset token.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Token is valid.',
        ], 200);
    }

    /**
     * Get human-readable error message for password reset status
     */
    private function getResetErrorMessage($status)
    {
        return match ($status) {
            Password::INVALID_USER => 'No user found with this email address.',
            Password::INVALID_TOKEN => 'Invalid or expired reset token.',
            default => 'An error occurred while resetting your password. Please try again.',
        };
    }
}
