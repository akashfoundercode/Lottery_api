<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreAgentRequest;
use App\Http\Requests\Api\Admin\UpdateAgentRequest;
use App\Models\Agent;

class AgentController extends Controller
{
    use HasOffsetLimit;

    // Create Agent
    public function store(StoreAgentRequest $request)
    {
        $agent = Agent::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Agent created successfully.',
            'data' => $agent,
        ], 201);
    }

    // Agent List
    public function index(\Illuminate\Http\Request $request)
    {
        $agents = $this->paginateWithOffset(Agent::latest(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Agent list fetched successfully.',
            'data' => $agents,
        ], 200);
    }

    // Agent Details
    public function show(Agent $agent)
    {
        return response()->json([
            'success' => true,
            'message' => 'Agent details fetched successfully.',
            'data' => $agent,
        ], 200);
    }

    // Update Agent
    public function update(UpdateAgentRequest $request, Agent $agent)
    {
        $data = $request->validated();

        // Password blank/null ho to existing password ko change mat karo
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $agent->update($data);

        $agent->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Agent updated successfully.',
            'data' => $agent,
        ], 200);
    }

    // Activate / Deactivate Agent
    public function toggleStatus(Agent $agent)
    {
        $agent->status = $agent->status === 'active'
            ? 'inactive'
            : 'active';

        $agent->save();

        return response()->json([
            'success' => true,
            'message' => $agent->status === 'active'
                ? 'Agent activated successfully.'
                : 'Agent deactivated successfully.',
            'data' => [
                'id' => $agent->id,
                'agent_id' => $agent->agent_id,
                'status' => $agent->status,
            ],
        ], 200);
    }
}
