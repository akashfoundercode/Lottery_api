<?php

namespace App\Services;

use App\Models\AgentNotification;

class AgentNotificationService
{
    public static function send(int $agentId, string $type, string $title, string $message, array $data = []): void
    {
        AgentNotification::create([
            'agent_id' => $agentId,
            'type'     => $type,
            'title'    => $title,
            'message'  => $message,
            'data'     => $data ?: null,
        ]);
    }
}
