<?php

namespace App\Realtime\Channels;

use App\Exceptions\RealtimeProtocolException;
use App\Models\LiveSession;
use App\Models\User;
use App\Services\Sessions\RealtimeSessionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LiveSessionChannelAuthorizer
{
    public function __construct(private readonly RealtimeSessionService $realtime) {}

    /** @return array{session_id:string,channel_name:string,recipient_id:string,expires_at:\DateTimeInterface} */
    public function authorize(User $actor, string $channelName): array
    {
        $prefix = 'private-live-session.';
        if (! str_starts_with($channelName, $prefix)) {
            throw new RealtimeProtocolException('invalid_channel', 'Only private live-session channels are supported.');
        }
        $sessionId = substr($channelName, strlen($prefix));
        if (! Str::isUuid($sessionId)) {
            throw new RealtimeProtocolException('invalid_channel', 'The channel does not contain a valid session id.');
        }
        $session = LiveSession::query()->whereKey($sessionId)->first();
        if ($session === null || ! Gate::forUser($actor)->allows('realtime', $session)) {
            throw new RealtimeProtocolException('channel_forbidden', 'The user is not an active participant in this session.');
        }

        try {
            return $this->realtime->authorizeChannel($actor, $sessionId, $channelName);
        } catch (\Throwable $exception) {
            throw new RealtimeProtocolException('channel_unavailable', $exception->getMessage());
        }
    }
}
