<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreResultRequest;
use App\Http\Requests\Api\Admin\UpdateResultRequest;
use App\Models\Result;
use App\Models\ResultPrize;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResultController extends Controller
{
    use HasOffsetLimit;

    public function index(Request $request): JsonResponse
    {
        $results = $this->paginateWithOffset(
            Result::with(['game', 'prizes'])->latest('result_date')->latest(),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Result list fetched successfully.',
            'data'    => $results->through(fn($r) => $this->serializeResult($r)),
        ]);
    }

    public function store(StoreResultRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if ($request->hasFile('result_image')) {
                $data['result_image'] = $request->file('result_image')->store('results', 'public');
            }

            $result = Result::create([
                'game_id'      => $data['game_id'],
                'title'        => $data['title'],
                'result_date'  => $data['result_date'],
                'description'  => $data['description'] ?? null,
                'result_image' => $data['result_image'] ?? null,
                'status'       => $data['status'] ?? 'active',
            ]);

            $this->syncPrizes($result, $data['prizes'], $request);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Result created successfully.',
            'data'    => $this->serializeResult($result->fresh(['game', 'prizes'])),
        ], 201);
    }

    public function show(Result $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Result details fetched successfully.',
            'data'    => $this->serializeResult($result->load(['game', 'prizes'])),
        ]);
    }

    public function update(UpdateResultRequest $request, Result $result): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if ($request->hasFile('result_image')) {
                $this->deleteFile($result->result_image);
                $data['result_image'] = $request->file('result_image')->store('results', 'public');
            }

            $resultData = [];
            foreach (['game_id', 'title', 'result_date', 'description', 'result_image', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $resultData[$field] = $data[$field];
                }
            }

            $result->update($resultData);

            if (isset($data['prizes'])) {
                // Delete old prize images then records
                foreach ($result->prizes as $prize) {
                    $this->deleteFile($prize->prize_image);
                }
                $result->prizes()->delete();
                $this->syncPrizes($result, $data['prizes'], $request);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Result updated successfully.',
            'data'    => $this->serializeResult($result->fresh(['game', 'prizes'])),
        ]);
    }

    public function destroy(Result $result): JsonResponse
    {
        $result->delete(); // soft delete

        return response()->json([
            'success' => true,
            'message' => 'Result deleted successfully.',
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $result = Result::onlyTrashed()->findOrFail($id);
        $result->restore();

        return response()->json([
            'success' => true,
            'message' => 'Result restored successfully.',
            'data'    => $this->serializeResult($result->fresh(['game', 'prizes'])),
        ]);
    }

    public function toggleStatus(Result $result): JsonResponse
    {
        $result->update([
            'status' => $result->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => $result->status === 'active'
                ? 'Result activated successfully.'
                : 'Result deactivated successfully.',
            'data'    => $this->serializeResult($result->fresh(['game', 'prizes'])),
        ]);
    }

    // ─── Private Helpers ────────────────────────────────────────────────────────

    private function syncPrizes(Result $result, array $prizes, Request $request): void
    {
        $game = $result->game ?? $result->load('game')->game;

        // Auto-calculated from game
        $totalBooksSold = $game->total_books ?? 0;
        $bookSize       = $game->book_size ?? 10;
        $totalTickets   = $totalBooksSold * $bookSize;
        $bookPrice      = (float) ($game->ticket_price ?? 0) * $bookSize;
        $ticketPrice    = (float) ($game->ticket_price ?? 0);

        foreach ($prizes as $index => $prizeData) {
            $imagePath = null;
            $fileKey   = "prizes.{$index}.prize_image";

            if ($request->hasFile($fileKey)) {
                $imagePath = $request->file($fileKey)->store('results/prizes', 'public');
            }

            ResultPrize::create([
                'result_id'       => $result->id,
                'rank'            => $prizeData['rank'],
                'prize_name'      => $prizeData['prize_name'] ?? null,
                'prize_type'      => $prizeData['prize_type'],
                'prize_amount'    => $prizeData['prize_amount'],
                'prize_image'     => $imagePath,
                'winner_name'     => $prizeData['winner_name'] ?? null,
                'winner_ticket_number' => $prizeData['winner_ticket_number'] ?? null,
                'winner_book_number'   => $prizeData['winner_book_number'] ?? null,
                'total_books_sold'=> $totalBooksSold,
                'total_tickets'   => $totalTickets,
                'book_price'      => $bookPrice,
                'ticket_price'    => $ticketPrice,
            ]);
        }
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function serializeResult(Result $result): array
    {
        return [
            'id'           => $result->id,
            'game_id'      => $result->game_id,
            'title'        => $result->title,
            'result_date'  => $result->result_date?->format('Y-m-d'),
            'description'  => $result->description,
            'status'       => $result->status,
            'result_image' => $result->result_image
                ? Storage::disk('public')->url($result->result_image)
                : null,
            'game' => $result->relationLoaded('game') && $result->game ? [
                'id'        => $result->game->id,
                'game_id'   => $result->game->game_id,
                'game_name' => $result->game->game_name,
                'draw_date' => $result->game->draw_date?->format('Y-m-d'),
                'draw_time' => $result->game->draw_time,
                'game_image_url' => $result->game->game_image_url,
            ] : null,
            'prizes' => $result->relationLoaded('prizes')
                ? $result->prizes->map(fn($p) => [
                    'id'               => $p->id,
                    'rank'             => $p->rank,
                    'prize_name'       => $p->prize_name,
                    'prize_type'       => $p->prize_type,
                    'prize_amount'     => $p->prize_amount,
                    'prize_image_url'  => $p->prize_image_url,
                    'winner_name'      => $p->winner_name,
                    'winner_ticket_number' => $p->winner_ticket_number,
                    'winner_book_number'   => $p->winner_book_number,
                    'total_books_sold' => $p->total_books_sold,
                    'total_tickets'    => $p->total_tickets,
                    'book_price'       => $p->book_price,
                    'ticket_price'     => $p->ticket_price,
                ])->values()
                : [],
            'created_at' => $result->created_at?->toISOString(),
            'updated_at' => $result->updated_at?->toISOString(),
            'deleted_at' => $result->deleted_at?->toISOString(),
        ];
    }
}
