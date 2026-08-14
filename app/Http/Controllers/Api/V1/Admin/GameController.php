<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreGameRequest;
use App\Models\Game;

class GameController extends Controller
{
   // Create Game
public function store(StoreGameRequest $request)
{
    // Create Game
    $game = Game::create($request->validated());

    return response()->json([
        'success' => true,
        'message' => 'Game created successfully.',
        'data' => $game,
    ], 201);
}

// Game List
public function index()
{
    $games = Game::latest()->paginate(10);

    return response()->json([
        'success' => true,
        'message' => 'Game list fetched successfully.',
        'data' => $games,
    ], 200);
}
}