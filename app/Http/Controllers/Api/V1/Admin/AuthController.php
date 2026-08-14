<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\LoginRequest;
use App\Http\Requests\Api\Admin\ChangePasswordRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($admin->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
            ], 403);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'admin' => $admin,
        ], 200);
    }

// Admin Logout
public function logout()
{
    $admin = auth('admin')->user();

    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized.',
        ], 401);
    }

    $admin->currentAccessToken()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Logout successful.',
    ], 200);
}

// Change Password
public function changePassword(ChangePasswordRequest $request)
{
    // Logged-in admin
    $admin = auth('admin')->user();

    // Check current password
    if (!Hash::check($request->current_password, $admin->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Current password is incorrect.',
        ], 400);
    }

    // Update new password
    $admin->update([
        'password' => Hash::make($request->new_password),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password changed successfully.',
    ], 200);
}

}