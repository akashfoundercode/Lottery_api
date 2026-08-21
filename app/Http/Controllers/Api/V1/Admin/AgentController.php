<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Concerns\HasOffsetLimit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreAgentRequest;
use App\Http\Requests\Api\Admin\UpdateAgentRequest;
use App\Models\Agent;
use Illuminate\Support\Facades\Storage;

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
            'data' => $this->serializeAgent($agent),
        ], 201);
    }

    // Agent List
    public function index(\Illuminate\Http\Request $request)
    {
        $agents = $this->paginateWithOffset(Agent::latest(), $request);
        $agents->getCollection()->transform(
            fn (Agent $agent) => $this->serializeAgent($agent)
        );

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
            'data' => $this->serializeAgent($agent),
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

        if ($request->hasFile('profile_photo')) {
            if ($agent->profile_photo) {
                Storage::disk('public')->delete($agent->profile_photo);
            }

            $data['profile_photo'] = $request
                ->file('profile_photo')
                ->store('agents/photos', 'public');
        }

        $agent->update($data);

        $agent->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Agent updated successfully.',
            'data' => $this->serializeAgent($agent),
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

    private function serializeAgent(Agent $agent): array
    {
        $data = $agent->toArray();

        $data['profile_photo_path'] = $agent->getRawOriginal('profile_photo');
        $data['profile_photo'] = $agent->profile_photo_url;
        $data['profile_photo_url'] = $agent->profile_photo_url;

        return $data;
    }
}
