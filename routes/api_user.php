<?php

use App\Http\Controllers\Api\V1\User\UserSearchController;
use App\Http\Controllers\Api\V1\Admin\ContactSettingController;
use App\Http\Controllers\Api\V1\Admin\GameBannerController;
use App\Http\Controllers\Api\V1\Admin\GameLiveLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/user')->group(function () {
    Route::get('/home', [UserSearchController::class, 'home']);
    Route::get('/search', [UserSearchController::class, 'search']);
    Route::get('/games/upcoming', [UserSearchController::class, 'upcomingGames']);
    Route::get('/games/live', [UserSearchController::class, 'liveGames']);
    Route::get('/games/{game}', [UserSearchController::class, 'gameDetail']);
    Route::get('/agents/first-party', [UserSearchController::class, 'firstPartyAgents']);
    Route::get('/agents/{agent}', [UserSearchController::class, 'agentDetail']);
    Route::get('/results', [UserSearchController::class, 'results']);
    Route::get('/results/history', [UserSearchController::class, 'resultsHistory']);
    Route::get('/results/{result}', [UserSearchController::class, 'resultDetail']);
    Route::get('/contact-details', [ContactSettingController::class, 'show']);
    Route::get('/live-banners', [GameBannerController::class, 'all']);
    Route::get('/games/{game}/live-banners', [GameBannerController::class, 'index']);
    Route::get('/games/{game}/live-links', [GameLiveLinkController::class, 'index']);
    Route::post('/my-tickets', [UserSearchController::class, 'myTickets']);
});
