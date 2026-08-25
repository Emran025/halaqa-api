<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\UpdateSessionReportRequest;
use App\Http\Resources\Api\V1\Reports\ReportResponseResource;
use App\Models\LiveSession;
use App\Services\Reports\SessionReportService;
use Illuminate\Support\Facades\Gate;

class UpdateSessionReportController extends Controller
{
    public function __invoke(UpdateSessionReportRequest $request, LiveSession $session, SessionReportService $service): ReportResponseResource
    {
        $report = $service->get($session);
        Gate::authorize('update', $report);

        return new ReportResponseResource($service->update($request->user(), $report, $request->validated()));
    }
}
