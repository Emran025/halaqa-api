<?php

namespace App\Http\Resources\Api\V1\Realtime;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealtimeSessionResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['realtime_session' => ['session_id' => (string) $this->resource['session_id'], 'channel_name' => (string) $this->resource['channel_name'], 'websocket_url' => (string) $this->resource['websocket_url'], 'expires_at' => $this->resource['expires_at']->toISOString(), 'direct_p2p_only' => true, 'signaling_transport' => 'laravel_websocket', 'ice_candidate_policy' => 'host_only', 'media_transport' => 'webrtc_peer_to_peer']];
    }
}
