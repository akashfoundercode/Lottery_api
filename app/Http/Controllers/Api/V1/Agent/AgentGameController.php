<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class AgentGameController extends Controller
{
    // Agent ke assigned games ki list
    public function index(Request $request)
    {
        $agent = $request->user();

        $games = Game::whereHas('books', fn($q) => $q->where('agent_id', $agent->id))
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Assigned games fetched successfully.',
            'data'    => $games,
        ]);
    }

    // Single game details (sirf agar agent assigned ho)
    public function show(Request $request, Game $game)
    {
        $agent = $request->user();

        $isAssigned = $game->books()->where('agent_id', $agent->id)->exists();

        if (! $isAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'This game is not assigned to you.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Game details fetched successfully.',
            'data'    => $game,
        ]);
    }
}
