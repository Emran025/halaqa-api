<?php

namespace App\Http\Resources\Api\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherDocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'certificate_type' => $this->certificate_type,
            'certificate_type_other' => $this->certificate_type_other,
            'riwayah' => $this->riwayah,
            'issuing_place' => $this->issuing_place,
            'issuing_date' => $this->issuing_date?->format('Y-m-d'),
            'file_url' => null,
            'has_file' => $this->storage_path !== null,
        ];
    }
}
