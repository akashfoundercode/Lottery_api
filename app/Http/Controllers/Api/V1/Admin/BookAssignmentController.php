<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookStatus;
use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\AssignBooksRequest;
use App\Models\Agent;
use App\Models\Book;
use App\Models\BookAssignment;
use App\Services\AgentNotificationService;
use Illuminate\Support\Facades\DB;

class BookAssignmentController extends Controller
{
    use HasOffsetLimit;

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
        $assignedAt = now();
        $expiryAt = $request->expiry_at ? $request->expiry_at : $assignedAt->copy()->addHour();

        DB::transaction(function () use (
            $books,
            $agent,
            $assignedAt,
            $expiryAt,
            &$assignments
        ) {
            foreach ($books as $book) {
                $assignment = BookAssignment::create([
                    'book_id' => $book->id,
                    'agent_id' => $agent->id,
                    'assigned_at' => $assignedAt,
                    'expiry_at' => $expiryAt,
                ]);

                $book->load('game');
                $book->update([
                    'agent_id' => $agent->id,
                    'status' => BookStatus::ASSIGNED,
                    'assigned_at' => $assignedAt,
                    'expiry_at' => $expiryAt,
                ]);

                $assignments[] = $assignment;
            }

            AgentNotificationService::send(
                $agent->id,
                'book_assigned',
                'Books Assigned',
                count($assignments) . ' book(s) assigned to you. Expiry: ' . $expiryAt->format('d M Y h:i A'),
                ['book_ids' => collect($assignments)->pluck('book_id')->toArray(), 'expiry_at' => $expiryAt]
            );
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
                'expires_at' => $expiryAt,
                'assignments' => $assignments,
            ],
        ], 200);
    }

    public function history(\Illuminate\Http\Request $request)
    {
        [$assignments, $pagination] = $this->offsetItems(
            BookAssignment::with(['book.game', 'agent'])->latest('assigned_at'),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Assignment history fetched successfully.',
            'data' => $assignments,
            'pagination' => $pagination,
        ], 200);
    }

    public function expireExpiredAssignments()
    {
        $expiredBooks = Book::where('status', BookStatus::ASSIGNED)
            ->whereNotNull('expiry_at')
            ->where('expiry_at', '<=', now())
            ->get();

        $expiredCount = 0;

        foreach ($expiredBooks as $book) {
            $book->update([
                'status' => BookStatus::UNSOLD_BY_ADMIN,
                'agent_id' => null,
                'unsold_at' => now(),
            ]);

            $expiredCount++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Expired assigned books processed successfully.',
            'data' => [
                'expired_count' => $expiredCount,
                'book_ids' => $expiredBooks->pluck('id')->values(),
            ],
        ], 200);
    }
}
