<?php

namespace App\Events\Notifications;

use App\Models\LiveSession;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class SessionEnded implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly LiveSession $session) {}
}
