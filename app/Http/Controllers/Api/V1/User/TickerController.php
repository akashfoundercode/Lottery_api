<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Ticker;

class TickerController extends Controller
{
    public function index()
    {
        $tickers = Ticker::where('status', 'active')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active tickers fetched successfully.',
            'data' => $tickers,
        ], 200);
    }
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:100',
        ]);

        $search = $request->q;

        $tickets = Ticket::with([
            'book.game'
        ])
        ->where('ticket_number', 'like', "%{$search}%")
        ->orWhereHas('book.game', function ($query) use ($search) {
            $query->where('game_id', 'like', "%{$search}%")
                ->orWhere('game_name', 'like', "%{$search}%");
        })
        ->get();

        return response()->json([
            'success' => true,
            'message' => $tickets->isEmpty()
                ? 'No tickets found.'
                : 'Tickets search results fetched successfully.',
            'data' => $tickets,
        ]);
    }
}
