<?php

namespace App\Http\Resources\Api\V1\Realtime;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealtimeChannelAuthorizationResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['authorization' => ['authorized' => (bool) $this->resource['authorized'], 'channel_name' => (string) $this->resource['channel_name'], 'session_id' => (string) $this->resource['session_id'], 'recipient_id' => (string) $this->resource['recipient_id'], 'expires_at' => $this->resource['expires_at']?->toISOString()]];
    }
}
