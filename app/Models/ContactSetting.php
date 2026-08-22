<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = [
        'contact_number',
        'email',
        'address',
        'website',
        'whatsapp_url',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'twitter_url',
    ];
}
