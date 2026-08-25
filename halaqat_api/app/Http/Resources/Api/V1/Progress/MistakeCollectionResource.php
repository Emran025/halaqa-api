<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MistakeCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['mistakes' => collect($this->resource->items())->map(fn ($mistake) => ['id' => (string) $mistake->id, 'tracking_detail_id' => (string) $mistake->tracking_detail_id, 'edition_id' => (int) $mistake->edition_id, 'ayah_id' => (int) $mistake->ayah_id, 'word_index' => (int) $mistake->word_index, 'mistake_type' => $mistake->mistakeType?->code, 'source_role' => $mistake->source_role, 'note' => $mistake->note, 'created_by_user_id' => (string) $mistake->created_by_user_id, 'created_at' => $mistake->created_at?->toISOString(), 'updated_at' => $mistake->updated_at?->toISOString()])->values()->all(), 'meta' => ['current_page' => $this->resource->currentPage(), 'per_page' => $this->resource->perPage(), 'total' => $this->resource->total(), 'last_page' => $this->resource->lastPage()]];
    }
}
