<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Reports\ReportResponseResource;
use App\Models\LiveSession;
use App\Services\Reports\SessionReportService;
use Illuminate\Support\Facades\Gate;

class GetSessionReportController extends Controller
{
    public function __invoke(LiveSession $session, SessionReportService $service): ReportResponseResource
    {
        $report = $service->get($session);
        Gate::authorize('view', $report);

        return new ReportResponseResource($report);
    }
}
