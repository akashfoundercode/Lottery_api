<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class UserSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('q')) {
            $this->merge([
                'q' => trim((string) $this->input('q')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required.',
            'q.string' => 'Search query must be a valid text value.',
            'q.max' => 'Search query may not be greater than 100 characters.',
        ];
    }
}
