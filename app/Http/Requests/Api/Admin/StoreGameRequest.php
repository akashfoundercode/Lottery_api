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
        'ticket_price'      => 'required|numeric|min:1',
        'book_size'         => 'required|integer|min:1',
        'total_books'       => 'required|integer|min:1',
        'draw_date'         => 'required|date',
        'draw_time'         => 'required|date_format:H:i:s',
        'youtube_live_url'  => 'nullable|url|max:255',
        'facebook_live_url' => 'nullable|url|max:255',
        'status'            => 'required|in:' . implode(',', array_column(GameStatus::cases(), 'value')),
    ];
}

   public function messages(): array
 {
    return [
        'game_name.required' => 'Game name is required.',
        'game_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'game_id.required' => 'Game ID is required.',
        'game_id.unique' => 'Game ID already exists.',
        'ticket_price.required' => 'Ticket price is required.',
        'book_size.required' => 'Book size is required.',
        'total_books.required' => 'Total books is required.',
        'draw_date.required' => 'Draw date is required.',
        'draw_time.required' => 'Draw time is required.',
        'youtube_live_url.url' => 'Invalid YouTube URL.',
        'facebook_live_url.url' => 'Invalid Facebook URL.',
        'status.required' => 'Status is required.',
    ];
  }
}