<?php

namespace App\Events\Reports;

use App\Models\SessionReport;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class SessionReportRealtimeUpdated implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly SessionReport $report) {}
}
