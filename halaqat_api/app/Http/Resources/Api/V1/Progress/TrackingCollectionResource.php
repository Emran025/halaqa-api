<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['trackings' => collect($this->resource->items())->map(fn ($tracking) => ['id' => (string) $tracking->id, 'student_id' => (string) $tracking->student_id, 'halaqa_id' => $tracking->membership?->halaqa_id, 'date' => $tracking->date->format('Y-m-d'), 'attendance_type' => $tracking->attendance_type, 'note' => $tracking->note, 'behavior_note' => $tracking->behavior_note, 'details' => TrackingDetailResource::collection($tracking->details)->resolve($request),
            'created_at' => $tracking->created_at?->toISOString(), 'updated_at' => $tracking->updated_at?->toISOString()])->values()->all(), 'meta' => ['current_page' => $this->resource->currentPage(), 'per_page' => $this->resource->perPage(), 'total' => $this->resource->total(), 'last_page' => $this->resource->lastPage()]];
    }
}
