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
            'game_id' => [
                'sometimes',
                'required',
                'exists:games,id',
            ],
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'result_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'result_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }
}
