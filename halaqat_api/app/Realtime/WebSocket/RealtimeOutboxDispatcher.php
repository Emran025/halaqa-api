<?php

namespace App\Realtime\WebSocket;

use App\Models\RealtimeOutboxMessage;
use App\Realtime\Signaling\RealtimeServerEventEnvelopeFactory;
use Throwable;

class RealtimeOutboxDispatcher
{
    public function __construct(
        private readonly ConnectionManager $connections,
        private readonly FrameCodec $codec,
        private readonly RealtimeServerEventEnvelopeFactory $envelopes,
    ) {}

    public function dispatch(int $limit = 50): int
    {
        $delivered = 0;
        $messages = RealtimeOutboxMessage::query()
            ->whereNull('delivered_at')
            ->with('session')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(max(1, min($limit, 100)))
            ->get();

        foreach ($messages as $message) {
            if (! $this->connections->hasParticipant((string) $message->session_id, (string) $message->recipient_id)) {
                continue;
            }

            $message->forceFill([
                'attempts' => ((int) $message->attempts) + 1,
                'last_attempted_at' => now(),
            ])->save();

            try {
                $envelope = $this->envelopes->make($message);
                $encoded = json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                if (! $this->connections->sendToParticipant((string) $message->session_id, (string) $message->recipient_id, $encoded, $this->codec)) {
                    continue;
                }
                $message->forceFill(['delivered_at' => now()])->save();
                $delivered++;
            } catch (Throwable) {
                continue;
            }
        }

        return $delivered;
    }
}
