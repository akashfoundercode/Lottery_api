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

    protected function prepareForValidation(): void
    {
        // Support POST method spoofing for multipart/form-data
        if ($this->input('_method')) {
            $this->offsetUnset('_method');
        }

        $data = [];

        if ($this->has('game_id')) {
            $data['game_id'] = strtoupper((string) $this->input('game_id'));
        }

        if ($this->has('status')) {
            $data['status'] = match (strtolower((string) $this->input('status'))) {
                'live' => GameStatus::ACTIVE->value,
                'upcoming' => GameStatus::INACTIVE->value,
                default => strtolower((string) $this->input('status')),
            };
        }

        $this->merge($data);
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
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
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

            'banners' => ['sometimes', 'nullable', 'array'],
            'banners.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],

            // IDs of banners to delete
            'delete_banner_ids'   => ['sometimes', 'nullable', 'array'],
            'delete_banner_ids.*' => ['integer'],
        ];
    }
}
