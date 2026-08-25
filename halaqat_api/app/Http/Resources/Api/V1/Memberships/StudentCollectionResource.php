<?php

namespace App\Http\Resources\Api\V1\Memberships;

use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'students' => UserResource::collection($this->resource->items()),
            'pagination' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ],
        ];
    }
}
