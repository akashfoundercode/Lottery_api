<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameLiveLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameLiveLinkController extends Controller
{
    public function index(Game $game): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Game live links fetched successfully.',
            'data' => $this->serializeLinks($game),
        ]);
    }

    public function store(Request $request, Game $game): JsonResponse
    {
        $data = $this->validatedData($request, true);
        $this->createLinks($game, $data['links']);

        return response()->json([
            'success' => true,
            'message' => 'Game live links stored successfully.',
            'data' => $this->serializeLinks($game->fresh()),
        ], 201);
    }

    public function update(Request $request, Game $game): JsonResponse
    {
        $data = $this->validatedData($request, true);

        DB::transaction(function () use ($game, $data) {
            $game->liveLinks()->delete();
            $this->createLinks($game, $data['links']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Game live links updated successfully.',
            'data' => $this->serializeLinks($game->fresh()),
        ]);
    }

    public function destroy(Game $game, GameLiveLink $link): JsonResponse
    {
        if ($link->game_id !== $game->id) {
            return response()->json([
                'success' => false,
                'message' => 'This live link does not belong to the selected game.',
            ], 404);
        }

        $link->delete();

        return response()->json([
            'success' => true,
            'message' => 'Game live link deleted successfully.',
        ]);
    }

    private function validatedData(Request $request, bool $required): array
    {
        return $request->validate([
            'links' => [$required ? 'required' : 'sometimes', 'array', 'min:1'],
            'links.*.platform' => ['required', 'in:youtube,facebook'],
            'links.*.url' => ['required', 'url', 'max:2048'],
        ]);
    }

    private function createLinks(Game $game, array $links): void
    {
        foreach ($links as $sortOrder => $link) {
            GameLiveLink::create([
                'game_id' => $game->id,
                'platform' => $link['platform'],
                'url' => $link['url'],
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function serializeLinks(Game $game): array
    {
        return [
            'game_id' => $game->id,
            'game_code' => $game->game_id,
            'game_name' => $game->game_name,
            'live_urls' => $game->liveLinks->map(fn (GameLiveLink $link) => [
                'id' => $link->id,
                'platform' => $link->platform,
                'url' => $link->url,
                'sort_order' => $link->sort_order,
            ])->values(),
        ];
    }
}
