<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\GameStatus;

class StoreGameRequest extends FormRequest
{
    // Authorize Request
    public function authorize(): bool
    {
        return true;
    }

    // Validation Rules
    public function rules(): array
{
    return [
        'game_name'         => 'required|string|max:100',
        'game_id'           => 'required|string|max:50|unique:games,game_id',
        'game_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'ticket_price'      => 'required|numeric|min:1',
        'book_size'         => 'required|integer|min:1',
        'total_books'       => 'required|integer|min:1',
        'draw_date'         => 'required|date',
        'draw_time'         => 'required|date_format:H:i:s',
        'youtube_live_url'  => 'nullable|url|max:255',
        'facebook_live_url' => 'nullable|url|max:255',
        'status'            => 'required|in:' . implode(',', array_column(GameStatus::cases(), 'value')),
        'banners'           => 'nullable|array',
        'banners.*'         => 'image|mimes:jpg,jpeg,png,webp|max:5120',
    ];
}
}