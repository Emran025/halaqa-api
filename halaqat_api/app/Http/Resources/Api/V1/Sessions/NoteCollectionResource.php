<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return ['notes' => NoteItemResource::collection($paginator->getCollection())->resolve($request), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]];
    }
}
