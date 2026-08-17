<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_name' => [
                'required',
                'string',
                'max:100',
            ],

            'agent_id' => [
                'required',
                'string',
                'max:50',
                'unique:agents,agent_id',
            ],

            'mobile_number' => [
                'required',
                'string',
                'max:20',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'agent_type' => [
                'required',
                Rule::in([
                    'first_party',
                    'third_party',
                ]),
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:agents,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }
}
