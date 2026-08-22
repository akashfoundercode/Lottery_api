<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{

    // Mass Assignable Fields
    protected $fillable = [
        'game_name',
        'game_image',
        'game_id',
        'ticket_price',
        'book_size',
        'total_books',
        'draw_date',
        'draw_time',
        'youtube_live_url',
        'facebook_live_url',
        'status',
        'went_live_at',
    ];

    protected $appends = ['game_image_url'];

    public function getGameImageUrlAttribute(): ?string
    {
        if (! $this->game_image) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($this->game_image, ['http://', 'https://'])) {
            return $this->game_image;
        }

        return request()->getSchemeAndHttpHost()
            . '/storage/'
            . ltrim($this->game_image, '/');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(GameBanner::class)->orderBy('sort_order');
    }

    public function liveLinks(): HasMany
    {
        return $this->hasMany(GameLiveLink::class)->orderBy('sort_order');
    }
    

    // Attribute Casting
    protected $casts = [
        'ticket_price'  => 'decimal:2',
        'draw_date'     => 'date',
        'status'        => GameStatus::class,
        'went_live_at'  => 'datetime',
    ];
}