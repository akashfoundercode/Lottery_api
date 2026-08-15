<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    // Mass Assignable Fields
    protected $fillable = [
        'book_id',
        'game_id',
        'ticket_number',
    ];

    // Ticket belongs to a Book
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // Ticket belongs to a Game
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}