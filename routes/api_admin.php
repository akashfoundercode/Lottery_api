<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\GameController;

Route::prefix('v1/admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:admin')->group(function () {
    Route::get('/profile', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'admin' => $request->user(),
        ]);
    });
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    // Change Password
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    // Create Game
    Route::post('/games', [GameController::class, 'store']);
    Route::get('/games', [GameController::class, 'index']);
});

   