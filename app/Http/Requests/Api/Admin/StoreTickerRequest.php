<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTickerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:100',
            ],
            'message' => [
                'required',
                'string',
                'max:255',
            ],
            'is_live' => [
                'nullable',
                'boolean',
            ],
            'status' => [
                'nullable',
                'in:active,inactive',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }
}
