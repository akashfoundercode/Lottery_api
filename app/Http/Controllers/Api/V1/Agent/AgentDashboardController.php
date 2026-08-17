<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    // Agent Dashboard
    public function index(Request $request)
    {
        $agent = $request->user();

        $books = Book::where('agent_id', $agent->id);

        return response()->json([
            'success' => true,
            'message' => 'Agent dashboard fetched successfully.',
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'agent_id' => $agent->agent_id,
                    'agent_name' => $agent->agent_name,
                    'agent_type' => $agent->agent_type,
                    'status' => $agent->status,
                ],

                'statistics' => [
                    'total_books' => (clone $books)->count(),
                    'available_books' => (clone $books)
                        ->where('status', 'available')
                        ->count(),
                    'assigned_books' => (clone $books)
                        ->where('status', 'assigned')
                        ->count(),
                    'sold_books' => (clone $books)
                        ->where('status', 'sold')
                        ->count(),
                    'unsold_books' => (clone $books)
                        ->where('status', 'unsold')
                        ->count(),
                    'unsold_by_admin_books' => (clone $books)
                        ->where('status', 'unsold_by_admin')
                        ->count(),
                ],
            ],
        ], 200);
    }
}
