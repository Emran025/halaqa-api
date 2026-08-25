<?php

namespace App\Events\LiveSession;

use App\Models\LiveSession;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LiveSessionRealtimeEvent implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly LiveSession $session,
        public readonly string $eventType,
    ) {}
}
