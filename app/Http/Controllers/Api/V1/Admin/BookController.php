<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookStatus;
use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ImportBookTicketsRequest;
use App\Models\Book;
use App\Models\BookStatusHistory;
use App\Models\Game;
use App\Models\Ticket;
use App\Services\TicketSpreadsheetImporter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    use HasOffsetLimit;

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
    public function index(Request $request)
    {
        $limit  = min(100, max(1, (int) $request->query('limit', 50)));
        $page   = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $query = Book::with('game')->latest();
        $total = (clone $query)->count();
        $books = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'success' => true,
            'message' => 'Book list fetched successfully.',
            'data'    => $books,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $limit,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $limit),
                'has_more'     => $page < ceil($total / $limit),
            ],
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

    // Admin: Update Book Status (sold <-> unsold correction)
    public function updateStatus(Request $request)
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
        ]);

        $book = Book::with('agent')->findOrFail($request->book_id);

        if (! in_array($book->status, [BookStatus::SOLD, BookStatus::UNSOLD])) {
            return response()->json([
                'success' => false,
                'message' => 'Only sold or unsold books can be corrected by admin.',
            ], 422);
        }

        $newStatus = $book->status === BookStatus::SOLD ? BookStatus::UNSOLD : BookStatus::SOLD;

        DB::transaction(function () use ($book, $newStatus) {
            $oldStatus = $book->status->value;

            $book->update([
                'status'    => $newStatus,
                'sold_at'   => $newStatus === BookStatus::SOLD ? now() : null,
                'unsold_at' => $newStatus === BookStatus::UNSOLD ? now() : null,
            ]);

            BookStatusHistory::create([
                'book_id'    => $book->id,
                'agent_id'   => $book->agent_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus->value,
                'changed_at' => now(),
            ]);
        });

        $updated = $book->fresh()->load('agent');

        return response()->json([
            'success' => true,
            'message' => 'Book status updated to '.$newStatus->value.' by admin.',
            'data' => [
                'book_id'    => $updated->id,
                'book_number'=> $updated->book_id,
                'agent_id'   => $updated->agent_id,
                'agent_name' => $updated->agent?->agent_name,
                'status'     => $updated->status,
                'sold_at'    => $updated->sold_at,
                'unsold_at'  => $updated->unsold_at,
            ],
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
