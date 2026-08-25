<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $session = $this->resource;

        return ['session' => ['id' => (string) $session->id, 'halaqa_id' => (string) $session->halaqa_id, 'teacher' => ['id' => (string) $session->teacher->id, 'name' => $session->teacher->name, 'role' => $session->teacher->role], 'student' => ['id' => (string) $session->student->id, 'name' => $session->student->name, 'role' => $session->student->role], 'follow_up_item_id' => $session->follow_up_item_id, 'task_type' => $session->taskType->code, 'state' => $session->state, 'scheduled_at' => $session->scheduled_at?->toISOString(), 'requested_at' => $session->requested_at->toISOString(), 'accepted_at' => $session->accepted_at?->toISOString(), 'connected_at' => $session->connected_at?->toISOString(), 'ended_at' => $session->ended_at?->toISOString(), 'end_reason' => $session->end_reason, 'direct_p2p_only' => true, 'created_at' => $session->created_at->toISOString(), 'updated_at' => $session->updated_at->toISOString()]];
    }
}
