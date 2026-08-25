<?php

namespace App\Services\Sessions;

use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RealtimeSessionService
{
    public function configuration(LiveSession $session): array
    {
        return $this->configurationFor($session);
    }

    public function reconnect(LiveSession $session): array
    {
        DB::transaction(function () use ($session): void {
            $locked = LiveSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->state, ['accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected', 'direct_connection_unavailable'], true)) {
                throw new ApiConflictException('The session cannot start a reconnection attempt from its current state.', 'invalid_reconnect_state', 'session', $session->id);
            }
            $locked->update(['state' => 'reconnecting']);
            $session->setRawAttributes($locked->fresh()->getAttributes());
        });

        return $this->configurationFor($session->fresh());
    }

    public function authorizeChannel(User $actor, string $sessionId, string $channelName): array
    {
        $session = LiveSession::query()->whereKey($sessionId)->firstOrFail();
        $expected = 'private-live-session.'.$session->id;
        if ($channelName !== $expected) {
            throw new ApiConflictException('The channel does not belong to the requested session.', 'realtime_channel_mismatch', 'channel_name', $channelName);
        }
        if (in_array($session->state, ['requested', 'cancelled', 'rejected', 'ended'], true)) {
            throw new ApiConflictException('The realtime channel is no longer available for this session.', 'realtime_channel_unavailable', 'session', $session->id);
        }

        $recipientId = (string) ($session->teacher_id === $actor->id ? $session->student_id : $session->teacher_id);

        return ['authorized' => true, 'channel_name' => $channelName, 'session_id' => (string) $session->id, 'recipient_id' => $recipientId, 'expires_at' => now()->addMinutes(15)];
    }

    private function configurationFor(LiveSession $session): array
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $parsed = parse_url($appUrl);
        $scheme = (($parsed['scheme'] ?? 'http') === 'https') ? 'wss' : 'ws';
        $host = $parsed['host'] ?? 'localhost';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        return ['session_id' => (string) $session->id, 'channel_name' => 'private-live-session.'.$session->id, 'websocket_url' => $scheme.'://'.$host.$port.'/ws', 'expires_at' => now()->addMinutes(15), 'direct_p2p_only' => true, 'signaling_transport' => 'laravel_websocket', 'ice_candidate_policy' => 'host_only', 'media_transport' => 'webrtc_peer_to_peer'];
    }
}
