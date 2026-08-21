<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Enums\BookStatus;
use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookStatusHistory;
use App\Services\AgentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentBookController extends Controller
{
    use HasOffsetLimit;

    public function index(Request $request)
    {
        $agent = $request->user();
        $books = $this->paginateWithOffset(
            Book::with(['game', 'tickets'])
                ->where('agent_id', $agent->id)
                ->latest(),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Assigned books fetched successfully.',
            'data' => $books,
        ], 200);
    }

    public function show(Request $request, Book $book)
    {
        $agent = $request->user();

        if ($book->agent_id !== $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This book is not assigned to you.',
            ], 403);
        }

        $book->load(['game', 'tickets', 'agent']);

        return response()->json([
            'success' => true,
            'message' => 'Book details fetched successfully.',
            'data' => [
                'book_id'       => $book->id,
                'book_number'   => $book->book_id,
                'status'        => $book->status,
                'assigned_at'   => $book->assigned_at,
                'expiry_at'     => $book->expiry_at,
                'sold_at'       => $book->sold_at,
                'unsold_at'     => $book->unsold_at,
                'agent' => [
                    'id'         => $book->agent->id,
                    'agent_id'   => $book->agent->agent_id,
                    'agent_name' => $book->agent->agent_name,
                ],
                'game' => [
                    'id'        => $book->game->id,
                    'game_id'   => $book->game->game_id,
                    'game_name' => $book->game->game_name,
                    'draw_date' => $book->game->draw_date,
                    'draw_time' => $book->game->draw_time,
                    'status'    => $book->game->status,
                ],
                'total_tickets' => $book->tickets->count(),
                'tickets'       => $book->tickets->map(fn($t) => [
                    'id'            => $t->id,
                    'ticket_number' => $t->ticket_number,
                ]),
            ],
        ], 200);
    }

    public function markSold(Request $request)
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'agent_id' => 'required|integer|exists:agents,id',
        ]);

        $agent = $request->user();

        if ($agent->id !== (int) $request->agent_id) {
            return response()->json(['success' => false, 'message' => 'Agent ID does not match authenticated agent.'], 403);
        }

        $book = Book::with('game')->findOrFail($request->book_id);

        if ($book->agent_id !== $agent->id) {
            return response()->json(['success' => false, 'message' => 'This book is not assigned to you.'], 403);
        }

        if (! in_array($book->status, [BookStatus::ASSIGNED, BookStatus::UNSOLD])) {
            return response()->json(['success' => false, 'message' => 'This book cannot be marked as sold.'], 422);
        }

        // Lock check: agar game live hue 1 ghanta guzar gaya ya book unsold_by_admin hai
        if ($book->status === BookStatus::UNSOLD_BY_ADMIN) {
            return response()->json(['success' => false, 'message' => 'This book has been locked by admin and cannot be changed.'], 403);
        }

        $game = $book->game;
        if ($game && $game->went_live_at && now()->diffInMinutes($game->went_live_at) > 60) {
            return response()->json(['success' => false, 'message' => 'The 1-hour window after game went live has expired. Book status is locked.'], 403);
        }

        DB::transaction(function () use ($book, $agent) {
            $book->update(['status' => BookStatus::SOLD, 'sold_at' => now()]);
            BookStatusHistory::create([
                'book_id' => $book->id,
                'agent_id' => $agent->id,
                'old_status' => BookStatus::ASSIGNED->value,
                'new_status' => BookStatus::SOLD->value,
                'changed_at' => now(),
            ]);
            AgentNotificationService::send(
                $agent->id,
                'book_sold',
                'Book Marked Sold',
                'Book #' . $book->book_id . ' has been marked as sold.',
                ['book_id' => $book->id, 'book_number' => $book->book_id]
            );
        });

        $updated = $book->fresh()->load(['agent', 'tickets']);

        return response()->json([
            'success' => true,
            'message' => 'Book marked as sold successfully.',
            'data' => [
                'book_id'       => $updated->id,
                'book_number'   => $updated->book_id,
                'agent_id'      => $updated->agent_id,
                'agent_name'    => $updated->agent->agent_name,
                'status'        => $updated->status,
                'sold_at'       => $updated->sold_at,
                'total_tickets' => $updated->tickets->count(),
                'tickets'       => $updated->tickets->map(fn($t) => [
                    'id'            => $t->id,
                    'ticket_number' => $t->ticket_number,
                ]),
            ],
        ], 200);
    }

    public function markUnsold(Request $request)
    {
        $request->validate([
            'book_id' => 'required|integer|exists:books,id',
            'agent_id' => 'required|integer|exists:agents,id',
        ]);

        $agent = $request->user();

        if ($agent->id !== (int) $request->agent_id) {
            return response()->json(['success' => false, 'message' => 'Agent ID does not match authenticated agent.'], 403);
        }

        $book = Book::with('game')->findOrFail($request->book_id);

        if ($book->agent_id !== $agent->id) {
            return response()->json(['success' => false, 'message' => 'This book is not assigned to you.'], 403);
        }

        if (! in_array($book->status, [BookStatus::ASSIGNED, BookStatus::SOLD])) {
            return response()->json(['success' => false, 'message' => 'This book cannot be marked as unsold.'], 422);
        }

        // Lock check: agar game live hue 1 ghanta guzar gaya ya book unsold_by_admin hai
        if ($book->status === BookStatus::UNSOLD_BY_ADMIN) {
            return response()->json(['success' => false, 'message' => 'This book has been locked by admin and cannot be changed.'], 403);
        }

        $game = $book->game;
        if ($game && $game->went_live_at && now()->diffInMinutes($game->went_live_at) > 60) {
            return response()->json(['success' => false, 'message' => 'The 1-hour window after game went live has expired. Book status is locked.'], 403);
        }

        DB::transaction(function () use ($book, $agent) {
            $book->update(['status' => BookStatus::UNSOLD, 'unsold_at' => now()]);
            BookStatusHistory::create([
                'book_id' => $book->id,
                'agent_id' => $agent->id,
                'old_status' => BookStatus::ASSIGNED->value,
                'new_status' => BookStatus::UNSOLD->value,
                'changed_at' => now(),
            ]);
            AgentNotificationService::send(
                $agent->id,
                'book_unsold',
                'Book Marked Unsold',
                'Book #' . $book->book_id . ' has been marked as unsold.',
                ['book_id' => $book->id, 'book_number' => $book->book_id]
            );
        });

        $updated = $book->fresh()->load(['agent', 'tickets']);

        return response()->json([
            'success' => true,
            'message' => 'Book marked as unsold successfully.',
            'data' => [
                'book_id'       => $updated->id,
                'book_number'   => $updated->book_id,
                'agent_id'      => $updated->agent_id,
                'agent_name'    => $updated->agent->agent_name,
                'status'        => $updated->status,
                'unsold_at'     => $updated->unsold_at,
                'total_tickets' => $updated->tickets->count(),
                'tickets'       => $updated->tickets->map(fn($t) => [
                    'id'            => $t->id,
                    'ticket_number' => $t->ticket_number,
                ]),
            ],
        ], 200);
    }
}
