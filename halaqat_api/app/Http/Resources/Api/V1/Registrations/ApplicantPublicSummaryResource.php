<?php

namespace App\Http\Resources\Api\V1\Registrations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantPublicSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->student;

        return [
            'id' => (string) $student->id,
            'display_name' => $student->name,
            'avatar' => $student->avatar_path,
            'status' => $this->state,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'sensitive_fields_hidden' => true,
        ];
    }
}
