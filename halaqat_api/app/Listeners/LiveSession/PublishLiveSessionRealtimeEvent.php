<?php

namespace App\Listeners\LiveSession;

use App\Events\LiveSession\LiveSessionRealtimeEvent;
use App\Services\LiveSessions\RealtimeOutboxPublisher;

class PublishLiveSessionRealtimeEvent
{
    public function __construct(private readonly RealtimeOutboxPublisher $publisher) {}

    public function handle(LiveSessionRealtimeEvent $event): void
    {
        $this->publisher->publishSessionState($event->session, $event->eventType);
    }
}
