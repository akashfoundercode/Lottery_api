<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Enums\GameStatus;
use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\MyTicketsRequest;
use App\Http\Requests\Api\User\UserSearchRequest;
use App\Models\Agent;
use App\Models\Game;
use App\Models\Result;
use App\Models\Ticker;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserSearchController extends Controller
{
    use HasOffsetLimit;

    /**
     * HOME
     */
    public function home(Request $request): JsonResponse
    {
        [$offset, $limit] = $this->offsetLimit($request, 5);

        // Live game
        $liveGame = Game::where(
            'status',
            GameStatus::ACTIVE->value
        )
            ->orderBy('draw_date')
            ->orderBy('draw_time')
            ->first();

        // Upcoming games
        $upcomingGames = Game::whereIn(
            'status',
            [
                GameStatus::ACTIVE->value,
                'inactive',
            ]
        )
            ->orderBy('draw_date')
            ->orderBy('draw_time')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(
                fn (Game $game) => $this->serializeGame($game)
            )
            ->values();

        // First party agents
        $firstPartyAgents = Agent::where(
            'agent_type',
            'first_party'
        )
            ->where('status', 'active')
            ->orderBy('agent_name')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(
                fn (Agent $agent) => $this->serializeAgent($agent)
            )
            ->values();

        // Latest results
        $latestResults = Game::where(
            'status',
            GameStatus::COMPLETED->value
        )
            ->orderByDesc('draw_date')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(
                fn (Game $game) => $this->serializeGameResult($game)
            )
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Home data fetched successfully.',
            'data' => [
                'live_game' => $liveGame
                    ? $this->serializeGame($liveGame)
                    : null,

                'upcoming_games' => $upcomingGames,

                'first_party_agents' => $firstPartyAgents,

                'latest_results' => $latestResults,

                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                ],
            ],
        ], 200);
    }

    /**
     * SEARCH
     */
    public function search(UserSearchRequest $request): JsonResponse
    {
        $term = strtolower(trim($request->q));

        $ticketResults = Ticket::with([
            'book.game',
        ])
            ->where(function ($query) use ($term) {

                $query
                    ->whereRaw(
                        'LOWER(ticket_number) LIKE ?',
                        ['%' . $term . '%']
                    )

                    ->orWhereHas(
                        'book',
                        fn ($book) => $book->whereRaw(
                            'LOWER(book_id) LIKE ?',
                            ['%' . $term . '%']
                        )
                    )

                    ->orWhereHas(
                        'game',
                        fn ($game) => $game
                            ->whereRaw(
                                'LOWER(game_name) LIKE ?',
                                ['%' . $term . '%']
                            )
                            ->orWhereRaw(
                                'LOWER(game_id) LIKE ?',
                                ['%' . $term . '%']
                            )
                    );
            })
            ->get();

        $tickerResults = Ticker::where(
            'status',
            'active'
        )
            ->where(
                fn ($query) => $query
                    ->whereRaw(
                        'LOWER(title) LIKE ?',
                        ['%' . $term . '%']
                    )
                    ->orWhereRaw(
                        'LOWER(message) LIKE ?',
                        ['%' . $term . '%']
                    )
            )
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

                'draw_date' => $this->formatDate(
                    $book?->draw_date,
                    'Y-m-d'
                ),

                'book_status' => $this->normalizeStatus(
                    $book?->status
                ),

                'created_at' => $this->formatDate(
                    $ticket->created_at,
                    'Y-m-d H:i:s'
                ),
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

        [$offset, $limit] = $this->offsetLimit($request);

        $items = $results
            ->slice($offset, $limit)
            ->values();

        $pagination = [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $results->count(),
            'has_more' => (
                $offset + $items->count()
            ) < $results->count(),
        ];

        return response()->json([
            'success' => true,

            'message' => $items->isEmpty()
                ? 'No results found.'
                : 'Search results fetched successfully.',

            'data' => [
                'results' => $items,
                'pagination' => $pagination,
            ],
        ], 200);
    }

    /**
     * UPCOMING GAMES
     */
    public function upcomingGames(Request $request): JsonResponse
    {
        [
            $items,
            $pagination,
        ] = $this->offsetItems(
            Game::whereIn(
                'status',
                [
                    GameStatus::ACTIVE->value,
                    'inactive',
                ]
            )
                ->orderBy('draw_date')
                ->orderBy('draw_time'),
            $request
        );

        return $this->collectionResponse(
            'Upcoming games fetched successfully.',
            'games',
            $items->map(
                fn (Game $game) => $this->serializeGame($game)
            )->values(),
            $pagination
        );
    }

    /**
     * LIVE GAMES
     */
    public function liveGames(Request $request): JsonResponse
    {
        [
            $items,
            $pagination,
        ] = $this->offsetItems(
            Game::where(
                'status',
                GameStatus::ACTIVE->value
            )
                ->orderBy('draw_date')
                ->orderBy('draw_time'),
            $request
        );

        return $this->collectionResponse(
            'Live games fetched successfully.',
            'games',
            $items->map(
                fn (Game $game) => $this->serializeGame($game)
            )->values(),
            $pagination
        );
    }

    /**
     * GAME DETAIL
     */
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

    /**
     * FIRST PARTY AGENTS
     */
    public function firstPartyAgents(Request $request): JsonResponse
    {
        [
            $items,
            $pagination,
        ] = $this->offsetItems(
            Agent::where(
                'agent_type',
                'first_party'
            )
                ->where('status', 'active')
                ->orderBy('agent_name'),
            $request
        );

        return $this->collectionResponse(
            'First party agents fetched successfully.',
            'agents',
            $items->map(
                fn (Agent $agent) => $this->serializeAgent($agent)
            )->values(),
            $pagination
        );
    }

    /**
     * AGENT DETAIL
     */
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

    /**
     * RESULTS
     */
    public function results(Request $request): JsonResponse
    {
        [
            $items,
            $pagination,
        ] = $this->offsetItems(
            Result::with([
                'game',
                'prizes',
            ])
                ->where('status', 'active')
                ->orderByDesc('result_date'),
            $request
        );

        return $this->collectionResponse(
            'Results fetched successfully.',
            'results',
            $items->map(
                fn (Result $result) => $this->serializeResult($result)
            )->values(),
            $pagination
        );
    }

    /**
     * RESULTS HISTORY
     */
    public function resultsHistory(Request $request): JsonResponse
    {
        [
            $items,
            $pagination,
        ] = $this->offsetItems(
            Result::with([
                'game',
                'prizes',
            ])
                ->orderByDesc('result_date'),
            $request
        );

        return $this->collectionResponse(
            'Result history fetched successfully.',
            'results',
            $items->map(
                fn (Result $result) => $this->serializeResult($result)
            )->values(),
            $pagination
        );
    }

    /**
     * RESULT DETAIL
     */
    public function resultDetail(Result $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Result details fetched successfully.',
            'data' => [
                'result' => $this->serializeResult(
                    $result->load([
                        'game',
                        'prizes',
                    ])
                ),
            ],
        ], 200);
    }

    /**
     * MY TICKETS
     */
    public function myTickets(
        MyTicketsRequest $request
    ): JsonResponse {

        $agent = Agent::whereRaw(
            'REPLACE(mobile_number, " ", "") = ?',
            [$request->mobile]
        )->first();

        [
            $items,
            $pagination,
        ] = $this->offsetItems(
            Ticket::with([
                'book.game',
            ])
                ->whereHas(
                    'book',
                    fn ($query) => $query->where(
                        'agent_id',
                        $agent?->id
                    )
                )
                ->orderByDesc('created_at'),
            $request
        );

        $tickets = $items->map(
            function (Ticket $ticket) {

                $book = $ticket->book;
                $game = $book?->game;

                return [
                    'ticket_number' => $ticket->ticket_number,

                    'lottery_name' => $game?->game_name,

                    'draw_date' => $this->formatDate(
                        $book?->draw_date,
                        'Y-m-d'
                    ),

                    'book_status' => $this->normalizeStatus(
                        $book?->status
                    ),

                    'booking_date' => $this->formatDate(
                        $book?->assigned_at
                            ?? $book?->created_at,
                        'Y-m-d H:i:s'
                    ),

                    'purchase_date' => $this->formatDate(
                        $book?->assigned_at
                            ?? $book?->created_at,
                        'Y-m-d H:i:s'
                    ),
                ];
            }
        )->values();

        return response()->json([
            'success' => true,
            'message' => 'Tickets fetched successfully.',
            'data' => [
                'tickets' => $tickets,
                'pagination' => $pagination,
            ],
        ], 200);
    }

    /**
     * COMMON COLLECTION RESPONSE
     */
    private function collectionResponse(
        string $message,
        string $key,
        $items,
        array $pagination
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                $key => $items,
                'pagination' => $pagination,
            ],
        ], 200);
    }

    private function serializeGame(Game $game): array
    {
        $firstPartyAgents = Agent::where(
            fn ($query) => $query
                ->whereHas(
                    'books',
                    fn ($book) => $book->where(
                        'game_id',
                        $game->id
                    )
                )
                ->orWhereHas(
                    'bookAssignments.book',
                    fn ($book) => $book->where(
                        'game_id',
                        $game->id
                    )
                )
        )
            ->where(
                'agent_type',
                'first_party'
            )
            ->where(
                'status',
                'active'
            )
            ->orderBy('agent_name')
            ->get()
            ->map(
                fn (Agent $agent) =>
                    $this->serializeAgent($agent)
            )
            ->values();

        return [
            'id' => $game->id,

            'game_id' => $game->game_id,

            'game_name' => $game->game_name,

            'ticket_price' => $game->ticket_price,

            'draw_date' => $this->formatDate(
                $game->draw_date,
                'Y-m-d'
            ),

            'draw_time' => $this->formatDate(
                $game->draw_time,
                'H:i:s'
            ),

            'status' => $this->normalizeStatus(
                $game->status
            ),

            'youtube_live_url' => $game->youtube_live_url,

            'facebook_live_url' => $game->facebook_live_url,

            'book_size' => $game->book_size,

            'total_books' => $game->total_books,

            'first_party_agents' => $firstPartyAgents,

            'assigned_agents' => $firstPartyAgents,
        ];
    }

    /**
     * SERIALIZE AGENT
     */
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

            'profile_photo_url' => $agent->profile_photo
                ? asset(
                    'storage/' . $agent->profile_photo
                )
                : null,
        ];
    }

    /**
     * SERIALIZE RESULT
     */
    private function serializeResult(
        Game|Result $result
    ): array {

        if ($result instanceof Game) {

            return [
                'id' => $result->id,

                'game_id' => $result->game_id,

                'game_name' => $result->game_name,

                'draw_date' => $this->formatDate(
                    $result->draw_date,
                    'Y-m-d'
                ),

                'draw_time' => $this->formatDate(
                    $result->draw_time,
                    'H:i:s'
                ),

                'status' => $this->normalizeStatus(
                    $result->status
                ),

                'youtube_live_url' =>
                    $result->youtube_live_url,

                'facebook_live_url' =>
                    $result->facebook_live_url,
            ];
        }

        return [
            'id' => $result->id,

            'game_id' => $result->game?->game_id,

            'game_name' => $result->game?->game_name,

            'title' => $result->title,

            'result_date' => $this->formatDate(
                $result->result_date,
                'Y-m-d'
            ),

            'description' => $result->description,

            'status' => $this->normalizeStatus(
                $result->status
            ),

            'result_image' => $this->publicStorageUrl(
                $result->result_image
            ),

            'prizes' => $result->relationLoaded(
                'prizes'
            )
                ? $result->prizes
                    ->map(
                        fn ($prize) => [
                            'id' => $prize->id,

                            'game_id' => $result->game?->game_id,

                            'rank' => $prize->rank,

                            'prize_name' =>
                                $prize->prize_name,

                            'prize_type' =>
                                $prize->prize_type,

                            'prize_amount' =>
                                $prize->prize_amount,

                            'prize_image_url' =>
                                $prize->prize_image_url,
                        ]
                    )
                    ->values()
                : [],
        ];
    }

    /**
     * SERIALIZE GAME RESULT
     */
    private function serializeGameResult(
        Game $game
    ): array {

        return $this->serializeResult($game);
    }

    /**
     * FORMAT DATE / TIME
     */
    private function formatDate(
        $value,
        string $format
    ): ?string {

        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        if (
            is_string($value)
            && $value !== ''
        ) {

            try {

                return \Carbon\Carbon::parse(
                    $value
                )->format($format);

            } catch (\Throwable) {

                return $value;
            }
        }

        return $value;
    }

    /**
     * NORMALIZE ENUM STATUS
     */
    private function normalizeStatus($status): ?string
    {
        return $status instanceof \BackedEnum
            ? $status->value
            : $status;
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return request()->getSchemeAndHttpHost()
            . '/storage/'
            . ltrim($path, '/');
    }
}
