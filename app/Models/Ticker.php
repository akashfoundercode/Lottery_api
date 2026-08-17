<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticker extends Model
{
    protected $fillable = [
        'title',
        'message',
        'is_live',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'is_live' => 'boolean',
        'sort_order' => 'integer',
    ];
}
