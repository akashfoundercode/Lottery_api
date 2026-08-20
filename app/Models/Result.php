<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'game_id',
        'title',
        'result_image',
        'result_date',
        'description',
        'status',
    ];

    protected $casts = [
        'result_date' => 'date',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(ResultPrize::class)->orderBy('rank')->orderBy('prize_type');
    }
}
