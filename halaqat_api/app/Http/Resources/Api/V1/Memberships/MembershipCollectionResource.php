<?php

namespace App\Http\Resources\Api\V1\Memberships;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'memberships' => $this->resource->getCollection()
                ->map(fn ($membership) => (new MembershipResource($membership))->resolve($request))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
