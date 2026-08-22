<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;

class AssignBooksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $bookIds = $this->input('book_ids');

        if ($bookIds === null) {
            $bookIds = $this->input('bookIds')
                ?? $this->input('selected_book_ids')
                ?? $this->input('selectedBooks');
        }

        if ($bookIds === null && $this->filled('book_id')) {
            $bookIds = [$this->input('book_id')];
        }

        if (is_string($bookIds)) {
            $decodedBookIds = json_decode($bookIds, true);

            $bookIds = is_array($decodedBookIds)
                ? $decodedBookIds
                : array_filter(array_map('trim', explode(',', $bookIds)));
        }

        if (! is_array($bookIds)) {
            return;
        }

        $this->merge([
            'book_ids' => collect($bookIds)->map(
                fn ($book) => Book::query()
                    ->where(function ($query) use ($book) {
                        $query->where('id', data_get($book, 'id', $book))
                            ->orWhere('book_id', data_get($book, 'book_id', $book));
                    })
                    ->value('id')
            )->all(),
        ]);
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
