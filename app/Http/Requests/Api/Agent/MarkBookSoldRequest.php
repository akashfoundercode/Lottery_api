<?php

namespace App\Http\Requests\Api\Agent;

use Illuminate\Foundation\Http\FormRequest;

class MarkBookSoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
