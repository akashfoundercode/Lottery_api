<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_live_links')) {
            return;
        }

        DB::table('games')
            ->select(['id', 'youtube_live_url', 'facebook_live_url'])
            ->where(function ($query) {
                $query->whereNotNull('youtube_live_url')
                    ->orWhereNotNull('facebook_live_url');
            })
            ->orderBy('id')
            ->each(function ($game) {
                $links = [
                    'youtube' => $game->youtube_live_url,
                    'facebook' => $game->facebook_live_url,
                ];

                foreach ($links as $platform => $url) {
                    if (! $url) {
                        continue;
                    }

                    $exists = DB::table('game_live_links')
                        ->where('game_id', $game->id)
                        ->where('platform', $platform)
                        ->where('url', $url)
                        ->exists();

                    if (! $exists) {
                        DB::table('game_live_links')->insert([
                            'game_id' => $game->id,
                            'platform' => $platform,
                            'url' => $url,
                            'sort_order' => $platform === 'youtube' ? 0 : 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Existing live-link records are intentionally retained on rollback.
    }
};
