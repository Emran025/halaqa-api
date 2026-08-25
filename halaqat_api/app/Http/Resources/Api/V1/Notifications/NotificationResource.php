<?php

namespace App\Http\Resources\Api\V1\Notifications;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => [
                'event_type' => $payload['event_type'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => isset($payload['entity_id']) ? (string) $payload['entity_id'] : null,
                'session_id' => isset($payload['session_id']) && $payload['session_id'] !== null ? (string) $payload['session_id'] : null,
                'follow_up_item_id' => isset($payload['follow_up_item_id']) && $payload['follow_up_item_id'] !== null ? (string) $payload['follow_up_item_id'] : null,
                'action' => $payload['action'] ?? null,
                'action_path' => $payload['action_path'] ?? null,
            ],
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
