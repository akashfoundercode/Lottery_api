<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    // Update Agent
    public function authorize(): bool
    {
        return true;
    }

    // Validation Rules
    public function rules(): array
    {
        $agent = $this->route('agent');

        return [
            'agent_name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'agent_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('agents', 'agent_id')->ignore($agent->id),
            ],

            'mobile_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
            ],

            'whatsapp_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'agent_type' => [
                'sometimes',
                'required',
                Rule::in([
                    'first_party',
                    'third_party',
                ]),
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('agents', 'email')->ignore($agent->id),
            ],

            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }
}
