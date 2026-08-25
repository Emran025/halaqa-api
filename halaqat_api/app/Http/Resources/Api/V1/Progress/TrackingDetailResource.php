<?php

namespace App\Http\Resources\Api\V1\Progress;

use App\Http\Resources\Api\V1\Sessions\AnnotationResponseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => (int) $this->id, 'uuid' => (string) $this->uuid, 'tracking_id' => (string) $this->tracking_id, 'tracking_type' => $this->trackingType?->code, 'from_unit' => $this->unit($this->fromUnit), 'to_unit' => $this->unit($this->toUnit), 'actual_amount' => (float) $this->actual_amount, 'status' => $this->state, 'comment' => $this->comment, 'score' => $this->score === null ? null : (int) $this->score, 'gap' => $this->gap === null ? null : (float) $this->gap, 'mistakes' => $this->mistakes->map(fn ($mistake) => (new AnnotationResponseResource($mistake))->resolve($request)['mistake'])->values()->all(), 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }

    private function unit($unit): ?array
    {
        if ($unit === null) {
            return null;
        }

        return ['unit' => $unit->unitType?->code, 'amount' => 1.0, 'page_number' => (int) $unit->from_page, 'ayah_id' => (int) $unit->from_ayah_id];
    }
}
