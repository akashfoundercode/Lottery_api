<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\LoginRequest;
use App\Models\Agent;
use Illuminate\Support\Facades\Hash;

class AgentAuthController extends Controller
{ 
    // Agent Login
    public function login(LoginRequest $request)
    {
        $agent = Agent::where('email', $request->email)->first();

        if (! $agent || ! Hash::check($request->password, $agent->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($agent->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Agent account is inactive.',
            ], 403);
        }

        // Create Sanctum token
        $token = $agent->createToken('agent-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Agent login successful.',
            'data' => [
                'agent' => $agent,
                'token' => $token,
            ],
        ], 200);
    }
}
