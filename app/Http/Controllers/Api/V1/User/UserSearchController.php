<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\MyTicketsRequest;
use App\Http\Requests\Api\User\UserSearchRequest;
use App\Models\Agent;
use App\Models\Game;
use App\Models\Result;
use App\Models\Ticker;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class UserSearchController extends Controller
{
    public function home(): JsonResponse
    {
        $liveGame = Game::query()
            ->where('status', GameStatus::ACTIVE->value)
            ->orderBy('draw_date')
            ->orderBy('draw_time')
            ->first();

        $upcomingGames = Game::query()
            ->whereIn('status', [GameStatus::ACTIVE->value, 'inactive'])
            ->orderBy('draw_date')
            ->orderBy('draw_time')
            ->limit(5)
            ->get()
            ->map(fn (Game $game) => $this->serializeGame($game));

        $firstPartyAgents = Agent::query()
            ->where('agent_type', 'first_party')
            ->where('status', 'active')
            ->orderBy('agent_name')
            ->get()
            ->map(fn (Agent $agent) => $this->serializeAgent($agent));

        $latestResults = Game::query()
            ->where('status', GameStatus::COMPLETED->value)
            ->orderByDesc('draw_date')
            ->limit(5)
            ->get()
            ->map(fn (Game $game) => $this->serializeGameResult($game));

        return response()->json([
            'success' => true,
            'message' => 'Home data fetched successfully.',
            'data' => [
                'live_game' => $liveGame ? $this->serializeGame($liveGame) : null,
                'upcoming_games' => $upcomingGames,
                'first_party_agents' => $firstPartyAgents,
                'latest_results' => $latestResults,
            ],
        ], 200);
    }

    public function search(UserSearchRequest $request): JsonResponse
    {
        $searchTerm = strtolower(trim($request->q));

        $ticketResults = Ticket::query()
            ->with(['book.game'])
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(ticket_number) LIKE ?', ['%'.$searchTerm.'%'])
                    ->orWhereHas('book', function ($bookQuery) use ($searchTerm) {
                        $bookQuery->whereRaw('LOWER(book_id) LIKE ?', ['%'.$searchTerm.'%']);
                    })
                    ->orWhereHas('game', function ($gameQuery) use ($searchTerm) {
                        $gameQuery->whereRaw('LOWER(game_name) LIKE ?', ['%'.$searchTerm.'%'])
                            ->orWhereRaw('LOWER(game_id) LIKE ?', ['%'.$searchTerm.'%']);
                    });
            })
            ->get();

        $tickerResults = Ticker::query()
            ->where('status', 'active')
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%'.$searchTerm.'%'])
                    ->orWhereRaw('LOWER(message) LIKE ?', ['%'.$searchTerm.'%']);
            })
            ->get();

        $results = collect();

        foreach ($ticketResults as $ticket) {
            $book = $ticket->book;
            $game = $book?->game;

            $results->push([
                'type' => 'ticket',
                'ticket_number' => $ticket->ticket_number,
                'book_id' => $book?->book_id,
                'lottery_name' => $game?->game_name,
                'game_id' => $game?->game_id,
                'draw_date' => $this->formatDate($book?->draw_date, 'Y-m-d'),
                'book_status' => $this->normalizeStatus($book?->status),
                'created_at' => $this->formatDate($ticket->created_at, 'Y-m-d H:i:s'),
            ]);
        }

        foreach ($tickerResults as $ticker) {
            $results->push([
                'type' => 'ticker',
                'title' => $ticker->title,
                'message' => $ticker->message,
                'status' => $ticker->status,
            ]);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;
        $total = $results->count();
        $items = $results->slice(($page - 1) * $perPage, $perPage)->values();

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to' => min($page * $perPage, $total),
        ];

        if ($items->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No results found.',
                'data' => [
                    'results' => [],
                    'pagination' => $pagination,
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Search results fetched successfully.',
            'data' => [
                'results' => $items,
                'pagination' => $pagination,
            ],
        ], 200);
    }

    public function upcomingGames(): JsonResponse
    {
        $games = Game::query()
            ->whereIn('status', [GameStatus::ACTIVE->value, 'inactive'])
            ->orderBy('draw_date')
            ->orderBy('draw_time')
            ->get()
            ->map(fn (Game $game) => $this->serializeGame($game));

        return response()->json([
            'success' => true,
            'message' => 'Upcoming games fetched successfully.',
            'data' => [
                'games' => $games,
            ],
        ], 200);
    }

    public function liveGames(): JsonResponse
    {
        $games = Game::query()
            ->where('status', GameStatus::ACTIVE->value)
            ->orderBy('draw_date')
            ->orderBy('draw_time')
            ->get()
            ->map(fn (Game $game) => $this->serializeGame($game));

        return response()->json([
            'success' => true,
            'message' => 'Live games fetched successfully.',
            'data' => [
                'games' => $games,
            ],
        ], 200);
    }

    public function gameDetail(Game $game): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Game details fetched successfully.',
            'data' => [
                'game' => $this->serializeGame($game),
            ],
        ], 200);
    }

    public function firstPartyAgents(): JsonResponse
    {
        $agents = Agent::query()
            ->where('agent_type', 'first_party')
            ->where('status', 'active')
            ->orderBy('agent_name')
            ->get()
            ->map(fn (Agent $agent) => $this->serializeAgent($agent));

        return response()->json([
            'success' => true,
            'message' => 'First party agents fetched successfully.',
            'data' => [
                'agents' => $agents,
            ],
        ], 200);
    }

    public function agentDetail(Agent $agent): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Agent details fetched successfully.',
            'data' => [
                'agent' => $this->serializeAgent($agent),
            ],
        ], 200);
    }

    public function results(): JsonResponse
    {
        $results = Result::query()
            ->with('game')
            ->where('status', 'active')
            ->orderByDesc('result_date')
            ->paginate(10);

        $items = $results->getCollection()->map(fn (Result $result) => $this->serializeResult($result));
        $results->setCollection($items);

        return response()->json([
            'success' => true,
            'message' => 'Results fetched successfully.',
            'data' => [
                'results' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ],
        ], 200);
    }

    public function resultsHistory(): JsonResponse
    {
        $results = Result::query()
            ->with('game')
            ->orderByDesc('result_date')
            ->get()
            ->map(fn (Result $result) => $this->serializeResult($result));

        return response()->json([
            'success' => true,
            'message' => 'Result history fetched successfully.',
            'data' => [
                'results' => $results,
            ],
        ], 200);
    }

    public function resultDetail(Result $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Result details fetched successfully.',
            'data' => [
                'result' => $this->serializeResult($result->load('game')),
            ],
        ], 200);
    }

    public function myTickets(MyTicketsRequest $request): JsonResponse
    {
        $mobile = $request->mobile;

        $agent = Agent::query()
            ->whereRaw('REPLACE(mobile_number, " ", "") = ?', [$mobile])
            ->first();

        $tickets = Ticket::query()
            ->with(['book.game'])
            ->whereHas('book', function ($bookQuery) use ($agent) {
                $bookQuery->where('agent_id', $agent?->id);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($ticket) {
                $book = $ticket->book;
                $game = $book?->game;

                return [
                    'ticket_number' => $ticket->ticket_number,
                    'lottery_name' => $game?->game_name,
                    'draw_date' => $this->formatDate($book?->draw_date, 'Y-m-d'),
                    'book_status' => $this->normalizeStatus($book?->status),
                    'booking_date' => $this->formatDate($book?->assigned_at ?? $book?->created_at, 'Y-m-d H:i:s'),
                    'purchase_date' => $this->formatDate($book?->assigned_at ?? $book?->created_at, 'Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Tickets fetched successfully.',
            'data' => [
                'tickets' => $tickets->all(),
            ],
        ], 200);
    }

    private function serializeGame(Game $game): array
    {
        return [
            'id' => $game->id,
            'game_id' => $game->game_id,
            'game_name' => $game->game_name,
            'ticket_price' => $game->ticket_price,
            'draw_date' => $this->formatDate($game->draw_date, 'Y-m-d'),
            'draw_time' => $this->formatDate($game->draw_time, 'H:i:s'),
            'status' => $game->status,
            'youtube_live_url' => $game->youtube_live_url,
            'facebook_live_url' => $game->facebook_live_url,
            'book_size' => $game->book_size,
            'total_books' => $game->total_books,
        ];
    }

    private function serializeAgent(Agent $agent): array
    {
        return [
            'id' => $agent->id,
            'agent_id' => $agent->agent_id,
            'agent_name' => $agent->agent_name,
            'agent_type' => $agent->agent_type,
            'mobile_number' => $agent->mobile_number,
            'whatsapp_number' => $agent->whatsapp_number,
            'address' => $agent->address,
            'status' => $agent->status,
        ];
    }

    private function serializeResult(Game|Result $result): array
    {
        if ($result instanceof Game) {
            return [
                'id' => $result->id,
                'game_id' => $result->game_id,
                'game_name' => $result->game_name,
                'draw_date' => $this->formatDate($result->draw_date, 'Y-m-d'),
                'draw_time' => $this->formatDate($result->draw_time, 'H:i:s'),
                'status' => $result->status,
                'youtube_live_url' => $result->youtube_live_url,
                'facebook_live_url' => $result->facebook_live_url,
            ];
        }

        return [
            'id' => $result->id,
            'game_id' => $result->game?->game_id,
            'game_name' => $result->game?->game_name,
            'title' => $result->title,
            'result_date' => $this->formatDate($result->result_date, 'Y-m-d'),
            'status' => $result->status,
            'result_image' => $result->result_image ? Storage::disk('public')->url($result->result_image) : null,
        ];
    }

    private function serializeGameResult(Game $game): array
    {
        return $this->serializeResult($game);
    }

    private function formatDate($value, string $format): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        if (is_string($value) && $value !== '') {
            try {
                return \Carbon\Carbon::parse($value)->format($format);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    private function normalizeStatus($status): ?string
    {
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return $status;
    }
}
