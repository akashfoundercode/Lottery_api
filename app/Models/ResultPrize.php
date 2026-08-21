<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResultPrize extends Model
{
    protected $fillable = [
        'result_id',
        'rank',
        'prize_name',
        'prize_type',
        'prize_amount',
        'prize_image',
     
    ];

    protected $casts = [
        'prize_amount' => 'decimal:2',
        'book_price'   => 'decimal:2',
        'ticket_price' => 'decimal:2',
    ];

    protected $appends = ['prize_image_url'];

    public function getPrizeImageUrlAttribute(): ?string
    {
        if (! $this->prize_image) {
            return null;
        }

        if (Str::startsWith($this->prize_image, ['http://', 'https://'])) {
            return $this->prize_image;
        }

        return request()->getSchemeAndHttpHost() . '/storage/' . ltrim($this->prize_image, '/');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
