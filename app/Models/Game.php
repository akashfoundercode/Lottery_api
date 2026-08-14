<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;

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
    ];

    // Attribute Casting
    protected $casts = [
        'ticket_price' => 'decimal:2',
        'draw_date'    => 'date',
        'status'       => GameStatus::class,
    ];
}