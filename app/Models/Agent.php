<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'agent_name',
        'agent_id',
        'mobile_number',
        'whatsapp_number',
        'address',
        'agent_type',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Agent ke assigned books
    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'agent_id');
    }

    public function bookAssignments(): HasMany
    {
        return $this->hasMany(BookAssignment::class);
    }
}
