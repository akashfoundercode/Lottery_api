<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ResultPrize extends Model
{
    protected $fillable = [
        'result_id',
        'rank',
        'prize_name',
        'prize_type',
        'prize_amount',
        'prize_image',
        'winner_name',
        'winner_ticket_number',
        'winner_book_number',
        'total_books_sold',
        'total_tickets',
        'book_price',
        'ticket_price',
    ];

    protected $casts = [
        'prize_amount' => 'decimal:2',
        'book_price'   => 'decimal:2',
        'ticket_price' => 'decimal:2',
    ];

    protected $appends = ['prize_image_url'];

    public function getPrizeImageUrlAttribute(): ?string
    {
        return $this->prize_image ? Storage::disk('public')->url($this->prize_image) : null;
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
