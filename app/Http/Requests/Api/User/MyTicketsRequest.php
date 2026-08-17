<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class MyTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile')) {
            $this->merge([
                'mobile' => trim((string) $this->input('mobile')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                'digits:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => 'The mobile number is required.',
            'mobile.string' => 'The mobile number must be a string.',
            'mobile.digits' => 'The mobile number must be 10 digits.',
        ];
    }
}
