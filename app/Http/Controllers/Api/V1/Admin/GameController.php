<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreGameRequest;
use App\Http\Requests\Api\Admin\UpdateGameRequest;
use App\Models\Game;
use App\Models\Book;
use App\Models\BookAssignment;
use App\Models\Agent;
use App\Models\GameBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameController extends Controller
{
    use HasOffsetLimit;

    public function store(StoreGameRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('game_image')) {
            $data['game_image'] = $request->file('game_image')->store('games', 'public');
        }

        if (empty($data['game_id'])) {
            $data['game_id'] = $this->generateGameCode();
        }

        $game = Game::create($data);

        // Store banner images
        if ($request->hasFile('banners')) {
            foreach ($request->file('banners') as $i => $banner) {
                GameBanner::create([
                    'game_id'    => $game->id,
                    'image_path' => $banner->store('games/banners', 'public'),
                    'sort_order' => $i,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Game created successfully.',
            'data'    => $this->serializeGame($game->fresh(['banners'])),
        ], 201);
    }

    public function index(Request $request)
    {
        $games = $this->paginateWithOffset(
            Game::with('banners')->latest(),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Game list fetched successfully.',
            'data'    => $games->through(fn($g) => $this->serializeGame($g)),
        ]);
    }

    public function show(Game $game)
    {
        return response()->json([
            'success' => true,
            'message' => 'Game details fetched successfully.',
            'data'    => $this->serializeGame($game->load('banners')),
        ]);
    }

    public function update(UpdateGameRequest $request, Game $game)
    {
        $data = $request->validated();

        if ($request->hasFile('game_image')) {
            if ($game->game_image) {
                Storage::disk('public')->delete($game->game_image);
            }
            $data['game_image'] = $request->file('game_image')->store('games', 'public');
        }

        // Delete specific banners if requested
        if (!empty($data['delete_banner_ids'])) {
            $toDelete = GameBanner::whereIn('id', $data['delete_banner_ids'])
                ->where('game_id', $game->id)
                ->get();

            foreach ($toDelete as $banner) {
                Storage::disk('public')->delete($banner->image_path);
                $banner->delete();
            }
        }

        // Add new banners
        if ($request->hasFile('banners')) {
            $nextOrder = $game->banners()->max('sort_order') + 1;
            foreach ($request->file('banners') as $i => $banner) {
                GameBanner::create([
                    'game_id'    => $game->id,
                    'image_path' => $banner->store('games/banners', 'public'),
                    'sort_order' => $nextOrder + $i,
                ]);
            }
        }

        unset($data['delete_banner_ids'], $data['banners']);

        // Jab game pehli baar active ho tab went_live_at set karo
        if (
            isset($data['status']) &&
            $data['status'] === \App\Enums\GameStatus::ACTIVE->value &&
            $game->status !== \App\Enums\GameStatus::ACTIVE &&
            is_null($game->went_live_at)
        ) {
            $data['went_live_at'] = now();
        }

        $game->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Game updated successfully.',
            'data'    => $this->serializeGame($game->fresh(['banners'])),
        ]);
    }

    private function serializeGame(Game $game): array
    {
        return array_merge($game->toArray(), [
            'banners' => $game->relationLoaded('banners')
                ? $game->banners->map(fn($b) => [
                    'id'        => $b->id,
                    'image_url' => $b->image_url,
                    'sort_order'=> $b->sort_order,
                ])->values()
                : [],
        ]);
    }

    private function generateGameCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (Game::where('game_id', $code)->exists());

        return $code;
    }
}
