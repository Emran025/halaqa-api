<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\ListStudentReportsRequest;
use App\Http\Resources\Api\V1\Reports\ReportCollectionResource;
use App\Models\User;
use App\Services\Reports\SessionReportService;
use Illuminate\Support\Facades\Gate;

class ListStudentReportsController extends Controller
{
    public function __invoke(ListStudentReportsRequest $request, User $student, SessionReportService $service): ReportCollectionResource
    {
        Gate::authorize('view', $student);

        return new ReportCollectionResource($service->listForStudent($student, $request->validated()));
    }
}
