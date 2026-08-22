<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameLiveLink extends Model
{
    protected $fillable = ['game_id', 'platform', 'url', 'sort_order'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
