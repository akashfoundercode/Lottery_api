<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id'      => ['required', 'exists:games,id'],
            'title'        => ['required', 'string', 'max:255'],
            'result_date'  => ['required', 'date'],
            'description'  => ['nullable', 'string'],
            'result_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status'       => ['sometimes', Rule::in(['active', 'inactive'])],

            // Prize categories array
            'prizes'                    => ['required', 'array', 'min:1'],
            'prizes.*.rank'             => ['required', 'integer', 'min:1'],
            'prizes.*.prize_name'       => ['nullable', 'string', 'max:255'],
            'prizes.*.prize_type'       => ['required', Rule::in(['book_winner', 'ticket_winner'])],
            'prizes.*.prize_amount'     => ['required', 'numeric', 'min:0'],
            'prizes.*.prize_image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'prizes.*.winner_name'      => ['nullable', 'string', 'max:255'],
            'prizes.*.winner_ticket_number' => ['nullable', 'string', 'max:50'],
            'prizes.*.winner_book_number'   => ['nullable', 'string', 'max:50'],
        ];
    }
}
