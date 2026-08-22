<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameBanner;
use App\Models\GameLiveLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GameBannerController extends Controller
{
    public function all(): JsonResponse
    {
        $games = Game::with(['banners', 'liveLinks'])
            ->where(function ($query) {
                $query->whereHas('banners')
                    ->orWhereHas('liveLinks');
            })
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'All game live banners and URLs fetched successfully.',
            'data' => $games->map(fn (Game $game) => [
                'game_id' => $game->id,
                'game_code' => $game->game_id,
                'game_name' => $game->game_name,
                'live_urls' => $game->liveLinks->map(fn ($link) => [
                    'id' => $link->id,
                    'platform' => $link->platform,
                    'url' => $link->url,
                    'sort_order' => $link->sort_order,
                ])->values(),
                'banners' => $game->banners->map(fn (GameBanner $banner) => [
                    'id' => $banner->id,
                    'image_path' => $banner->image_path,
                    'image_url' => $banner->image_url,
                    'sort_order' => $banner->sort_order,
                ])->values(),
            ])->values(),
        ]);
    }

    public function index(Game $game): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Game live banners fetched successfully.',
            'data' => $this->serializeGame($game->load(['banners', 'liveLinks'])),
        ]);
    }

    public function store(Request $request, Game $game): JsonResponse
    {
        $data = $this->validatedData($request, true);
        $this->saveBanners($request, $data, $game);

        return response()->json([
            'success' => true,
            'message' => 'Game live banners stored successfully.',
            'data' => $this->serializeGame($game->fresh(['banners', 'liveLinks'])),
        ], 201);
    }

    public function update(Request $request, Game $game): JsonResponse
    {
        $data = $this->validatedData($request);
        $this->saveBanners($request, $data, $game);

        return response()->json([
            'success' => true,
            'message' => 'Game live banners updated successfully.',
            'data' => $this->serializeGame($game->fresh(['banners', 'liveLinks'])),
        ]);
    }

    public function destroy(Game $game, GameBanner $banner): JsonResponse
    {
        if ($banner->game_id !== $game->id) {
            return response()->json([
                'success' => false,
                'message' => 'This banner does not belong to the selected game.',
            ], 404);
        }

        Storage::disk('public')->delete($banner->image_path);
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Game live banner deleted successfully.',
        ]);
    }

    private function validatedData(Request $request, bool $isStore = false): array
    {
        $data = $request->validate([
            'youtube_live_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'facebook_live_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'live_urls' => ['sometimes', 'nullable', 'array'],
            'live_urls.*.platform' => ['required_with:live_urls', 'in:youtube,facebook'],
            'live_urls.*.url' => ['required_with:live_urls', 'url', 'max:2048'],
            'banners' => [$isStore ? 'required' : 'sometimes', 'nullable', 'array'],
                'banners.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'delete_banner_ids' => ['sometimes', 'nullable', 'array'],
            'delete_banner_ids.*' => ['integer'],
        ]);

        if (! isset($data['live_urls'])) {
            $data['live_urls'] = collect([
                ['platform' => 'youtube', 'url' => $data['youtube_live_url'] ?? null],
                ['platform' => 'facebook', 'url' => $data['facebook_live_url'] ?? null],
            ])->filter(fn ($link) => $link['url'])->values()->all();
        }

        return $data;
    }

    private function saveBanners(Request $request, array $data, Game $game): void
    {
        DB::transaction(function () use ($request, $data, $game) {
            if (array_key_exists('live_urls', $data) && $data['live_urls'] !== []) {
                $game->liveLinks()->delete();
                foreach ($data['live_urls'] as $sortOrder => $link) {
                    GameLiveLink::create([
                        'game_id' => $game->id,
                        'platform' => $link['platform'],
                        'url' => $link['url'],
                        'sort_order' => $sortOrder,
                    ]);
                }
            }

            foreach ($data['delete_banner_ids'] ?? [] as $bannerId) {
                $banner = $game->banners()->whereKey($bannerId)->first();
                if ($banner) {
                    Storage::disk('public')->delete($banner->image_path);
                    $banner->delete();
                }
            }

            $sortOrder = ((int) $game->banners()->max('sort_order')) + 1;
            foreach ($request->file('banners', []) as $index => $image) {
                GameBanner::create([
                    'game_id' => $game->id,
                    'image_path' => $image->store('games/banners', 'public'),
                    'sort_order' => $sortOrder + $index,
                ]);
            }
        });
    }

    private function serializeGame(Game $game): array
    {
        return [
            'game_id' => $game->id,
            'game_code' => $game->game_id,
            'game_name' => $game->game_name,
            'live_urls' => $game->liveLinks->map(fn ($link) => [
                'id' => $link->id,
                'platform' => $link->platform,
                'url' => $link->url,
                'sort_order' => $link->sort_order,
            ])->values(),
            'banners' => $game->banners->map(fn (GameBanner $banner) => [
                'id' => $banner->id,
                'image_path' => $banner->image_path,
                'image_url' => $banner->image_url,
                'sort_order' => $banner->sort_order,
            ])->values(),
        ];
    }

}
