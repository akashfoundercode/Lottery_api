<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportBookTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => [
                'required',
                'integer',
                'exists:games,id',
            ],

            'draw_date' => [
                'nullable',
                'date',
            ],

            'draw_time' => [
                'nullable',
                'date_format:H:i:s',
            ],

            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Ticket spreadsheet file is required.',
            'file.mimes' => 'Ticket spreadsheet must be a CSV, XLSX, or XLS file.',
            'draw_time.date_format' => 'Draw time must be in HH:MM:SS format.',
        ];
    }
}
