<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
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
        'profile_photo',
        'agent_type',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = ['profile_photo_url'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        if (Str::startsWith($this->profile_photo, ['http://', 'https://'])) {
            return $this->profile_photo;
        }

        return asset('storage/' . ltrim($this->profile_photo, '/'));
    }

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
