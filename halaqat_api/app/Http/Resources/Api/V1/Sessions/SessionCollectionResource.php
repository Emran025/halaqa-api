<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionCollectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return [
            'sessions' => $paginator->getCollection()
                ->map(fn ($session) => (new SessionResponseResource($session))->resolve($request)['session'])
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
