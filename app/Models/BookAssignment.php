<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookAssignment extends Model
{
    protected $fillable = [
        'book_id',
        'agent_id',
        'assigned_at',
        'expiry_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expiry_at' => 'datetime',
    ];

    // Assignment belongs to Book
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // Assignment belongs to Agent
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BookAssignment::class);
    }
}
