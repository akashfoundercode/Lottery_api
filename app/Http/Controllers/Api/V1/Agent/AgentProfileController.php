<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentProfileController extends Controller
{
    // Profile dekho
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile fetched successfully.',
            'data'    => $request->user(),
        ]);
    }

    // Profile update (name, mobile, whatsapp, address)
    public function update(Request $request)
    {
        $agent = $request->user();

        $data = $request->validate([
            'agent_name'      => 'sometimes|required|string|max:100',
            'mobile_number'   => 'sometimes|required|string|max:20',
            'whatsapp_number' => 'sometimes|nullable|string|max:20',
            'address'         => 'sometimes|nullable|string',
        ]);

        $agent->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $agent->fresh(),
        ]);
    }

    // Profile photo update
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $agent = $request->user();

        if ($agent->profile_photo) {
            Storage::disk('public')->delete($agent->profile_photo);
        }

        $path = $request->file('profile_photo')->store('agents/photos', 'public');
        $agent->update(['profile_photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'data'    => $agent->fresh(),
        ]);
    }
}
