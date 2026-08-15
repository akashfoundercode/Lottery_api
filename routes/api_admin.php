<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\GameController;
use App\Http\Controllers\Api\V1\Admin\BookController;
  
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
    // Game Details
    Route::get('/games/{game}', [GameController::class, 'show']);
    // Update Game
    Route::put('/games/{game}', [GameController::class, 'update']);
    // Generate Books & Tickets
    Route::post('/games/{game}/generate-books', [BookController::class, 'generate']);
    // Book List
    Route::get('/books', [BookController::class, 'index']);
    
});

   