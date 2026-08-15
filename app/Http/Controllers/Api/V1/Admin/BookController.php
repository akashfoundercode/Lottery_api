<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Game;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    // Generate Books and Tickets
    public function generate(Game $game)
    {
        DB::transaction(function () use ($game) {

            $bookSize = $game->book_size;
            $totalBooks = $game->total_books;

            // Existing books count
            $existingBooks = Book::where('game_id', $game->id)->count();

            // Starting ticket number
            $ticketNumber = ($existingBooks * $bookSize) + 1;

            for ($book = 1; $book <= $totalBooks; $book++) {

                // Book ID
                $bookId = 'BK' . str_pad(
                    $existingBooks + $book,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Create Book
                $newBook = Book::create([
                    'game_id'      => $game->id,
                    'book_id'      => $bookId,
                    'total_tickets'=> $bookSize,
                    'draw_date'    => $game->draw_date,
                    'draw_time'    => $game->draw_time,
                    'status'       => 'available',
                ]);

                // Generate Tickets
                for ($ticket = 1; $ticket <= $bookSize; $ticket++) {

                    Ticket::create([
                        'book_id'       => $newBook->id,
                        'game_id'       => $game->id,
                        'ticket_number' => str_pad(
                            $ticketNumber,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ),
                    ]);

                    $ticketNumber++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Books and tickets generated successfully.',
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
}