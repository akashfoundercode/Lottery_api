<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\AssignBooksRequest;
use App\Models\Agent;
use App\Models\Book;
use App\Models\BookAssignment;
use Illuminate\Support\Facades\DB;

class BookAssignmentController extends Controller
{
    // Assign multiple books to an agent
    public function store(AssignBooksRequest $request)
    {
        $agent = Agent::findOrFail($request->agent_id);

        // Only active agents can receive books
        if ($agent->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Inactive agent cannot be assigned books.',
            ], 422);
        }

        $bookIds = $request->book_ids;

        $books = Book::whereIn('id', $bookIds)
            ->lockForUpdate()
            ->get();

        // Make sure all requested books were found
        if ($books->count() !== count($bookIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more books were not found.',
            ], 422);
        }

        // Only available books can be assigned
        $unavailableBooks = $books->filter(
            fn (Book $book) => $book->status !== BookStatus::AVAILABLE
        );

        if ($unavailableBooks->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected books are not available.',
                'book_ids' => $unavailableBooks->pluck('id')->values(),
            ], 422);
        }

        $assignments = [];

        DB::transaction(function () use (
            $books,
            $agent,
            $request,
            &$assignments
        ) {
            foreach ($books as $book) {

                // Create assignment history
                $assignment = BookAssignment::create([
                    'book_id' => $book->id,
                    'agent_id' => $agent->id,
                    'assigned_at' => now(),
                    'expiry_at' => $request->expiry_at,
                ]);

                // Update Book
                $book->update([
                    'agent_id' => $agent->id,
                    'status' => BookStatus::ASSIGNED,
                    'assigned_at' => now(),
                    'expiry_at' => $request->expiry_at,
                ]);

                $assignments[] = $assignment;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Books assigned successfully.',
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'agent_id' => $agent->agent_id,
                    'agent_name' => $agent->agent_name,
                ],
                'total_books_assigned' => count($assignments),
                'assignments' => $assignments,
            ],
        ], 200);
    }
}
