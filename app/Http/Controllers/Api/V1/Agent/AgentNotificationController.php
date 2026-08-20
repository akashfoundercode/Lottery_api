<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentNotification;
use Illuminate\Http\Request;

class AgentNotificationController extends Controller
{
    // Saari notifications (unread pehle)
    public function index(Request $request)
    {
        $agent = $request->user();

        $notifications = AgentNotification::where('agent_id', $agent->id)
            ->orderByRaw('read_at IS NOT NULL')
            ->latest()
            ->get()
            ->map(fn($n) => [
                'id'      => $n->id,
                'type'    => $n->type,
                'title'   => $n->title,
                'message' => $n->message,
                'data'    => $n->data,
                'is_read' => $n->read_at !== null,
                'time'    => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications fetched successfully.',
            'unread_count' => AgentNotification::where('agent_id', $agent->id)->whereNull('read_at')->count(),
            'data'    => $notifications,
        ]);
    }

    // Single ya sab mark as read
    public function markRead(Request $request)
    {
        $agent = $request->user();

        $query = AgentNotification::where('agent_id', $agent->id)->whereNull('read_at');

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        $query->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
        ]);
    }
}
