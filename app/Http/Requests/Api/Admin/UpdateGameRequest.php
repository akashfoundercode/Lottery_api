<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\GameStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameRequest extends FormRequest
{
    // Update Game
    public function authorize(): bool
    {
        return true;
    }

    // Validation Rules
    public function rules(): array
    {
        $game = $this->route('game');

        return [
            'game_name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'game_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('games', 'game_id')->ignore($game->id),
            ],

            'game_image' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'ticket_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:1',
            ],

            'book_size' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],

            'total_books' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],

            'draw_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'draw_time' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
            ],

            'youtube_live_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'facebook_live_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in(
                    array_column(GameStatus::cases(), 'value')
                ),
            ],
        ];
    }
}