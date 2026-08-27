<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpItemCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return ['follow_up_items' => $paginator->getCollection()->map(fn ($item) => (new FollowUpItemResource($item))->resolve($request))->values()->all(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]];
    }
}
