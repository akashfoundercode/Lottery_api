<?php

use App\Http\Controllers\Api\V1\Agent\AgentAuthController;
use App\Http\Controllers\Api\V1\Agent\AgentBookController;
use App\Http\Controllers\Api\V1\Agent\AgentDashboardController;
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
        // Assigned Books
        Route::get('/books', [AgentBookController::class, 'index']);
        // Assigned Book Details
        Route::get('/books/{book}', [AgentBookController::class, 'show']);
        // Mark Book Sold
        Route::patch('/books/{book}/sold', [AgentBookController::class, 'markSold']);
        // Mark Book Unsold
        Route::patch('/books/{book}/unsold', [AgentBookController::class, 'markUnsold']);
    });
});
