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
        $game = Game::findOrFail($request->game_id);
        $ticketNumbers = $importer->ticketNumbersFromFile($request->file('file'));

        if (count($ticketNumbers) !== (int) $game->book_size) {
            throw ValidationException::withMessages([
                'file' => [
                    'Imported ticket count must match the game book size of '.$game->book_size.'.',
                ],
            ]);
        }

        $existingTicketNumbers = Ticket::whereIn('ticket_number', $ticketNumbers)
            ->pluck('ticket_number')
            ->all();

        if ($existingTicketNumbers !== []) {
            throw ValidationException::withMessages([
                'ticket_number' => [
                    'Ticket numbers already exist: '.implode(', ', $existingTicketNumbers).'.',
                ],
            ]);
        }

        try {
            $book = DB::transaction(function () use ($game, $request, $ticketNumbers) {
                $book = Book::create([
                    'game_id' => $game->id,
                    'book_id' => $this->nextBookId(),
                    'total_tickets' => count($ticketNumbers),
                    'draw_date' => $request->input('draw_date', $game->draw_date),
                    'draw_time' => $request->input('draw_time', $game->draw_time),
                    'status' => 'available',
                ]);

                $now = now();

                $tickets = collect($ticketNumbers)
                    ->map(fn (string $ticketNumber) => [
                        'book_id' => $book->id,
                        'game_id' => $game->id,
                        'ticket_number' => $ticketNumber,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                Ticket::insert($tickets);

                return $book;
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'ticket_number' => ['One or more ticket numbers already exist.'],
            ]);
        }

        $book->load([
            'game',
            'tickets',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Book and tickets imported successfully.',
            'imported_ticket_count' => count($ticketNumbers),
            'data' => $book,
        ], 201);
    }

    // Book List
    public function index()
    {
        $books = Book::with('game')
            ->latest()
            ->paginate(10);

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
