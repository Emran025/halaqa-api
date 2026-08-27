<?php

namespace App\Http\Resources\Api\V1\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['report' => (new SessionReportResource($this->resource))->resolve($request)];
    }
}
