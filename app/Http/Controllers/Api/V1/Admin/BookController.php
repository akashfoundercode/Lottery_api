<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ImportBookTicketsRequest;
use App\Models\Book;
use App\Models\Game;
use App\Models\Ticket;
use App\Services\TicketSpreadsheetImporter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    // Generate Books and Tickets
    public function generate(Game $game)
    {
        return response()->json([
            'success' => false,
            'message' => 'Automatic book and ticket generation has been disabled. Please import tickets from a spreadsheet.',
        ], 410);
    }

    // Import Book and Tickets from Spreadsheet
    public function import(
        ImportBookTicketsRequest $request,
        TicketSpreadsheetImporter $importer
    ) {
        try {
            $game = Game::findOrFail($request->game_id);
            $bookRows = $importer->bookRowsFromFile($request->file('file'));
            $allTicketNumbers = [];

            foreach ($bookRows as $index => $bookRow) {
                $ticketNumbers = $bookRow['tickets'];

                if (trim((string) ($bookRow['book_number'] ?? '')) === '') {
                    throw ValidationException::withMessages([
                        'file' => ['Book row '.($index + 2).' is missing a book number.'],
                    ]);
                }

                if ($ticketNumbers === []) {
                    throw ValidationException::withMessages([
                        'file' => ['Book row '.($index + 2).' has no ticket numbers.'],
                    ]);
                }

                $allTicketNumbers = [...$allTicketNumbers, ...$ticketNumbers];
            }

            $existingTicketNumbers = Ticket::whereIn('ticket_number', $allTicketNumbers)
                ->pluck('ticket_number')
                ->all();

            if ($existingTicketNumbers !== []) {
                throw ValidationException::withMessages([
                    'ticket_number' => [
                        'Ticket numbers already exist: '.implode(', ', $existingTicketNumbers).'.',
                    ],
                ]);
            }

            $createdBooks = DB::transaction(function () use ($game, $request, $bookRows) {
                $books = [];

                foreach ($bookRows as $bookRow) {
                    $book = Book::create([
                        'game_id' => $game->id,
                        'book_id' => $this->nextBookId(),
                        'total_tickets' => count($bookRow['tickets']),
                        'draw_date' => $request->input('draw_date', $game->draw_date),
                        'draw_time' => $request->input('draw_time', $game->draw_time),
                        'status' => 'available',
                    ]);

                    $now = now();

                    $tickets = collect($bookRow['tickets'])
                        ->map(fn (string $ticketNumber) => [
                            'book_id' => $book->id,
                            'game_id' => $game->id,
                            'ticket_number' => $ticketNumber,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all();

                    Ticket::insert($tickets);

                    $book->load('game', 'tickets');
                    $books[] = $book;
                }

                return $books;
            });

            return response()->json([
                'success' => true,
                'message' => 'Books and tickets imported successfully.',
                'imported_book_count' => count($createdBooks),
                'imported_ticket_count' => count($allTicketNumbers),
                'data' => $createdBooks,
            ], 201);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $firstMessage = collect($errors)
                ->flatten()
                ->first();

            return response()->json([
                'success' => false,
                'message' => $firstMessage ?? 'Spreadsheet validation failed.',
                'errors' => $errors,
            ], 422);
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => [
                    'ticket_number' => [$exception->getMessage()],
                ],
            ], 422);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => [
                    'file' => [$exception->getMessage()],
                ],
            ], 500);
        }
    }

    // Book List
    public function index()
    {
        $books = Book::with('game')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Book list fetched successfully.',
            'data' => $books,
        ], 200);
    }

    // Book Details
    public function show(Book $book)
    {
        $book->load([
            'game',
            'tickets',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book details fetched successfully.',
            'data' => $book,
        ], 200);
    }

    private function nextBookId(): string
    {
        $nextNumber = Book::lockForUpdate()->count() + 1;

        do {
            $bookId = 'BK'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Book::where('book_id', $bookId)->exists());

        return $bookId;
    }
}
