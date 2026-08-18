<?php

use App\Http\Controllers\Api\V1\Admin\AgentController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\BookAssignmentController;
use App\Http\Controllers\Api\V1\Admin\BookController;
use App\Http\Controllers\Api\V1\Admin\GameController;
use App\Http\Controllers\Api\V1\Admin\ResultController;
use App\Http\Controllers\Api\V1\Admin\TickerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:admin')->group(function () {
        Route::get('/profile', function (Request $request) {
            return response()->json([
                'success' => true,
                'admin' => $request->user(),
            ]);
        });

        // Tickers
        Route::post('/tickers', [TickerController::class, 'store']);
        Route::get('/tickers', [TickerController::class, 'index']);
        Route::get('/tickers/{ticker}', [TickerController::class, 'show']);
        Route::put('/tickers/{ticker}', [TickerController::class, 'update']);
        Route::delete('/tickers/{ticker}', [TickerController::class, 'destroy']);
        Route::patch('/tickers/{ticker}/toggle-status', [TickerController::class, 'toggleStatus']);

        Route::get('/results', [ResultController::class, 'index']);
        Route::post('/results', [ResultController::class, 'store']);
        Route::get('/results/{result}', [ResultController::class, 'show']);
        Route::put('/results/{result}', [ResultController::class, 'update']);
        Route::delete('/results/{result}', [ResultController::class, 'destroy']);
        Route::patch('/results/{result}/toggle-status', [ResultController::class, 'toggleStatus']);
    });
    Route::post('/books/import', [BookController::class, 'import'])->middleware('auth:admin');
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
    // Book Details
    Route::get('/books/{book}', [BookController::class, 'show']);
    // Create Agent
    Route::post('/agents', [AgentController::class, 'store']);
    // Agent List
    Route::get('/agents', [AgentController::class, 'index']);
    // Agent Details
    Route::get('/agents/{agent}', [AgentController::class, 'show']);
    // Update Agent
    Route::put('/agents/{agent}', [AgentController::class, 'update']);
    // Toggle Agent Status
    Route::patch('/agents/{agent}/toggle-status', [AgentController::class, 'toggleStatus']);
    // Book Assignment
    Route::post('/book-assignments', [BookAssignmentController::class, 'store']);
    Route::get('/book-assignments/history', [BookAssignmentController::class, 'history']);
    Route::post('/book-assignments/expire', [BookAssignmentController::class, 'expireExpiredAssignments']);

});
