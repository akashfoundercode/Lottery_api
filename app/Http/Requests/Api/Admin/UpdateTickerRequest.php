<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTickerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'message' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'is_live' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
            'status' => [
                'sometimes',
                'nullable',
                'in:active,inactive',
            ],
            'sort_order' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
