<?php

namespace App\Listeners\Reports;

use App\Events\Reports\SessionReportRealtimeUpdated;
use App\Services\LiveSessions\RealtimeOutboxPublisher;

class PublishSessionReportRealtimeUpdated
{
    public function __construct(private readonly RealtimeOutboxPublisher $publisher) {}

    public function handle(SessionReportRealtimeUpdated $event): void
    {
        $this->publisher->publishReportUpdated($event->report);
    }
}
