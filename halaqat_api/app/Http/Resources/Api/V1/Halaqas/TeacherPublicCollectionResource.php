<?php

namespace App\Http\Resources\Api\V1\Halaqas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherPublicCollectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return [
            'teachers' => $paginator->getCollection()
                ->map(fn ($teacher) => (new TeacherPublicSummaryResource($teacher))->resolve($request))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
