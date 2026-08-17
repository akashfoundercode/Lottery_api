<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\MarkBookSoldRequest;
use App\Models\Book;
use App\Models\BookStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentBookController extends Controller
{
    // Assigned Books
    public function index(Request $request)
    {
        $agent = $request->user();

        $books = Book::with([
            'game',
            'tickets',
        ])
            ->where('agent_id', $agent->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Assigned books fetched successfully.',
            'data' => $books,
        ], 200);
    }

    // Book Details
    public function show(Request $request, Book $book)
    {
        $agent = $request->user();

        if ($book->agent_id !== $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This book is not assigned to you.',
            ], 403);
        }

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

    // Mark Book Sold
    public function markSold(
        MarkBookSoldRequest $request,
        Book $book
    ) {
        $agent = $request->user();

        // Check book belongs to logged-in agent
        if ($book->agent_id !== $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This book is not assigned to you.',
            ], 403);
        }

        // Only assigned book can be sold
        if ($book->status !== BookStatus::ASSIGNED) {
            return response()->json([
                'success' => false,
                'message' => 'Only assigned books can be marked as sold.',
            ], 422);
        }

        DB::transaction(function () use ($book, $agent) {

            $oldStatus = $book->status->value;

            // Update book
            $book->update([
                'status' => BookStatus::SOLD,
                'sold_at' => now(),
            ]);

            // Save status history
            BookStatusHistory::create([
                'book_id' => $book->id,
                'agent_id' => $agent->id,
                'old_status' => $oldStatus,
                'new_status' => BookStatus::SOLD->value,
                'changed_at' => now(),
            ]);
        });

        $book->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Book marked as sold successfully.',
            'data' => $book,
        ], 200);
    }

    // Mark Book Unsold
    public function markUnsold(
        MarkBookSoldRequest $request,
        Book $book
    ) {
        $agent = $request->user();

        // Check book belongs to logged-in agent
        if ($book->agent_id !== $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This book is not assigned to you.',
            ], 403);
        }

        // Only assigned book can be marked unsold
        if ($book->status !== BookStatus::ASSIGNED) {
            return response()->json([
                'success' => false,
                'message' => 'Only assigned books can be marked as unsold.',
            ], 422);
        }

        DB::transaction(function () use ($book, $agent) {

            $oldStatus = $book->status->value;

            // Update book
            $book->update([
                'status' => BookStatus::UNSOLD,
                'unsold_at' => now(),
            ]);

            // Save status history
            BookStatusHistory::create([
                'book_id' => $book->id,
                'agent_id' => $agent->id,
                'old_status' => $oldStatus,
                'new_status' => BookStatus::UNSOLD->value,
                'changed_at' => now(),
            ]);
        });

        $book->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Book marked as unsold successfully.',
            'data' => $book,
        ], 200);
    }
}
