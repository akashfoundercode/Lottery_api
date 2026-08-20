<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GameBanner extends Model
{
    protected $fillable = ['game_id', 'image_path', 'sort_order'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
