<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MistakeCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return ['mistakes' => $paginator->getCollection()->map(fn ($mistake) => (new AnnotationResponseResource($mistake))->resolve($request)['mistake'])->values()->all(),
            'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]];
    }
}
