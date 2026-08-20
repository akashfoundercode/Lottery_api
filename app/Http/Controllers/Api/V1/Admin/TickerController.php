<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreTickerRequest;
use App\Http\Requests\Api\Admin\UpdateTickerRequest;
use App\Models\Ticker;

class TickerController extends Controller
{
    use HasOffsetLimit;

    public function index(\Illuminate\Http\Request $request)
    {
        $tickers = $this->paginateWithOffset(Ticker::orderBy('sort_order')
            ->latest()
            , $request);

        return response()->json([
            'success' => true,
            'message' => 'Ticker list fetched successfully.',
            'data' => $tickers,
        ], 200);
    }

    public function store(StoreTickerRequest $request)
    {
        $ticker = Ticker::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticker created successfully.',
            'data' => $ticker,
        ], 201);
    }

    public function show(Ticker $ticker)
    {
        return response()->json([
            'success' => true,
            'message' => 'Ticker details fetched successfully.',
            'data' => $ticker,
        ], 200);
    }

    public function update(UpdateTickerRequest $request, Ticker $ticker)
    {
        $ticker->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticker updated successfully.',
            'data' => $ticker,
        ], 200);
    }

    public function destroy(Ticker $ticker)
    {
        $ticker->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticker deleted successfully.',
        ], 200);
    }

    public function toggleStatus(Ticker $ticker)
    {
        $ticker->update([
            'status' => $ticker->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => $ticker->status === 'active'
                ? 'Ticker activated successfully.'
                : 'Ticker deactivated successfully.',
            'data' => $ticker,
        ], 200);
    }
}
