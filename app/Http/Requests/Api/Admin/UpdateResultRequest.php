<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id'      => ['sometimes', 'required', 'exists:games,id'],
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'result_date'  => ['sometimes', 'required', 'date'],
            'description'  => ['sometimes', 'nullable', 'string'],
            'result_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status'       => ['sometimes', 'required', Rule::in(['active', 'inactive'])],

            // Prizes — if sent, replaces all existing prizes
            'prizes'                    => ['sometimes', 'array', 'min:1'],
            'prizes.*.rank'             => ['required_with:prizes', 'integer', 'min:1'],
            'prizes.*.prize_name'       => ['nullable', 'string', 'max:255'],
            'prizes.*.prize_type'       => ['required_with:prizes', Rule::in(['book_winner', 'ticket_winner'])],
            'prizes.*.prize_image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

        ];
    }
}
