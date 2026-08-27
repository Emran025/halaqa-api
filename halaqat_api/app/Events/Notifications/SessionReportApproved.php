<?php

namespace App\Events\Notifications;

use App\Models\SessionReport;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class SessionReportApproved implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly SessionReport $report) {}
}
