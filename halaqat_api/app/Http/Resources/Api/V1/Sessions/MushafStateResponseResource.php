<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MushafStateResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $state = $this->resource;

        return ['mushaf_state' => ['edition_id' => (int) $state->edition_id, 'page_number' => (int) $state->page_number, 'surah_id' => $state->surah_id === null ? null : (int) $state->surah_id, 'ayah_id' => $state->ayah_id === null ? null : (int) $state->ayah_id, 'range' => $this->range(), 'updated_by' => (string) $state->updated_by_user_id, 'updated_at' => $state->updated_at?->toISOString(), 'version' => (int) $state->version]];
    }

    private function range(): ?array
    {
        $state = $this->resource;
        if ($state->range_from_page === null && $state->range_from_ayah_id === null && $state->range_to_page === null && $state->range_to_ayah_id === null) {
            return null;
        }

        return ['edition_id' => (int) $state->edition_id, 'start_page' => $state->range_from_page === null ? null : (int) $state->range_from_page, 'start_ayah_id' => $state->range_from_ayah_id === null ? null : (int) $state->range_from_ayah_id, 'end_page' => $state->range_to_page === null ? null : (int) $state->range_to_page, 'end_ayah_id' => $state->range_to_ayah_id === null ? null : (int) $state->range_to_ayah_id, 'end_ayah_number' => $state->rangeToAyah?->number_in_surah === null ? null : (int) $state->rangeToAyah->number_in_surah];
    }
}
