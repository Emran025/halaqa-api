<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\ApprovalReportRequest;
use App\Http\Requests\Api\V1\Reports\ReopenSessionReportRequest;
use App\Http\Requests\Api\V1\Reports\StudentAcknowledgmentReportRequest;
use App\Http\Resources\Api\V1\Reports\ReportResponseResource;
use App\Models\LiveSession;
use App\Services\Reports\SessionReportService;
use Illuminate\Support\Facades\Gate;

class SessionReportActionController extends Controller
{
    public function approve(ApprovalReportRequest $request, LiveSession $session, SessionReportService $service): ReportResponseResource
    {
        $report = $service->get($session);
        Gate::authorize('approve', $report);
        $data = $request->validated();

        return new ReportResponseResource($service->approve($request->user(), $report, $data['note'] ?? null, $data['client_operation_id']));
    }

    public function acknowledge(StudentAcknowledgmentReportRequest $request, LiveSession $session, SessionReportService $service): ReportResponseResource
    {
        $report = $service->get($session);
        Gate::authorize('acknowledge', $report);
        $data = $request->validated();

        return new ReportResponseResource($service->acknowledge($request->user(), $report, $data['action'], $data['note'] ?? null, $data['client_operation_id']));
    }

    public function reopen(ReopenSessionReportRequest $request, LiveSession $session, SessionReportService $service): ReportResponseResource
    {
        $report = $service->get($session);
        Gate::authorize('reopen', $report);
        $data = $request->validated();

        return new ReportResponseResource($service->reopen($request->user(), $report, $data['reason'], $data['client_operation_id']));
    }
}
