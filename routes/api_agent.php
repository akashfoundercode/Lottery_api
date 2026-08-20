<?php

use App\Http\Controllers\Api\V1\Agent\AgentAuthController;
use App\Http\Controllers\Api\V1\Agent\AgentBookController;
use App\Http\Controllers\Api\V1\Agent\AgentDashboardController;
use App\Http\Controllers\Api\V1\Agent\AgentGameController;
use App\Http\Controllers\Api\V1\Agent\AgentNotificationController;
use App\Http\Controllers\Api\V1\Agent\AgentProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/agent')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Agent Authentication
    |--------------------------------------------------------------------------
    */

    // Agent Login
    Route::post('/login', [AgentAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/dashboard', [AgentDashboardController::class, 'index']);
        // Profile
        Route::get('/profile', [AgentProfileController::class, 'show']);
        Route::post('/profile', [AgentProfileController::class, 'update']);
        Route::post('/profile/photo', [AgentProfileController::class, 'updatePhoto']);
        // Notifications
        Route::get('/notifications', [AgentNotificationController::class, 'index']);
        Route::post('/notifications/read', [AgentNotificationController::class, 'markRead']);
        // Assigned Games
        Route::get('/games', [AgentGameController::class, 'index']);
        Route::get('/games/{game}', [AgentGameController::class, 'show']);
        // Assigned Books
        Route::get('/books', [AgentBookController::class, 'index']);
        // Assigned Book Details
        Route::get('/books/{book}', [AgentBookController::class, 'show']);
        // Mark Book Sold
        Route::post('/books/sold', [AgentBookController::class, 'markSold']);
        // Mark Book Unsold
        Route::post('/books/unsold', [AgentBookController::class, 'markUnsold']);
    });
});
