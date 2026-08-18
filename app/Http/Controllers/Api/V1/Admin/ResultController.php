<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreResultRequest;
use App\Http\Requests\Api\Admin\UpdateResultRequest;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ResultController extends Controller
{
    public function index(): JsonResponse
    {
        $results = Result::with('game')
            ->latest('result_date')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Result list fetched successfully.',
            'data' => $results,
        ], 200);
    }

    public function store(StoreResultRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('result_image')) {
            $data['result_image'] = $request->file('result_image')->store('results', 'public');
        }

        $result = Result::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Result created successfully.',
            'data' => $this->serializeResult($result->fresh(['game'])),
        ], 201);
    }

    public function show(Result $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Result details fetched successfully.',
            'data' => $this->serializeResult($result->load('game')),
        ], 200);
    }

    public function update(UpdateResultRequest $request, Result $result): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('result_image')) {
            if ($result->result_image && Storage::disk('public')->exists($result->result_image)) {
                Storage::disk('public')->delete($result->result_image);
            }

            $data['result_image'] = $request->file('result_image')->store('results', 'public');
        }

        $result->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Result updated successfully.',
            'data' => $this->serializeResult($result->fresh(['game'])),
        ], 200);
    }

    public function destroy(Result $result): JsonResponse
    {
        if ($result->result_image && Storage::disk('public')->exists($result->result_image)) {
            Storage::disk('public')->delete($result->result_image);
        }

        $result->delete();

        return response()->json([
            'success' => true,
            'message' => 'Result deleted successfully.',
        ], 200);
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
            'data' => $this->serializeResult($result->fresh(['game'])),
        ], 200);
    }

    private function serializeResult(Result $result): array
    {
        return [
            'id' => $result->id,
            'game_id' => $result->game_id,
            'title' => $result->title,
            'result_date' => $result->result_date?->format('Y-m-d'),
            'status' => $result->status,
            'result_image' => $result->result_image ? Storage::disk('public')->url($result->result_image) : null,
            'game' => $result->relationLoaded('game') && $result->game ? [
                'id' => $result->game->id,
                'game_id' => $result->game->game_id,
                'game_name' => $result->game->game_name,
            ] : null,
            'created_at' => $result->created_at?->toISOString(),
            'updated_at' => $result->updated_at?->toISOString(),
        ];
    }
}
