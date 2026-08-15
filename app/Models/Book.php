<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    // Mass Assignable Fields
    protected $fillable = [
        'game_id',
        'book_id',
        'agent_id',
        'total_tickets',
        'draw_date',
        'draw_time',
        'status',
        'assigned_at',
        'expiry_at',
        'sold_at',
        'unsold_at',
    ];

    // Attribute Casting
    protected $casts = [
        'draw_date'  => 'date',
        'assigned_at' => 'datetime',
        'expiry_at'  => 'datetime',
        'sold_at'    => 'datetime',
        'unsold_at'  => 'datetime',
        'status'     => BookStatus::class,
    ];

    // Book belongs to a Game
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}