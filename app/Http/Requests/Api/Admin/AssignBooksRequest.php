<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignBooksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => [
                'required',
                'integer',
                'exists:agents,id',
            ],

            'book_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'book_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:books,id',
            ],

            'expiry_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }
}
