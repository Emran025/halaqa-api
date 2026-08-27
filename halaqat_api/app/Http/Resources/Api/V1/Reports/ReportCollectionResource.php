<?php

namespace App\Http\Resources\Api\V1\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return [
            'reports' => $paginator->getCollection()->map(fn ($report) => (new SessionReportResource($report))->resolve($request))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
