<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GameBanner extends Model
{
    protected $fillable = ['game_id', 'image_path', 'sort_order'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://'])) {
            return $this->image_path;
        }

        return request()->getSchemeAndHttpHost()
            . '/storage/'
            . ltrim($this->image_path, '/');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
