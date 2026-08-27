<?php

namespace App\Http\Resources\Api\V1\Notifications;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;

        return [
            'notifications' => $paginator->getCollection()->map(fn ($notification) => (new NotificationResource($notification))->resolve($request))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
