<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'student_id' => (string) $this->resource['student_id'],
            'last_completed' => $this->resource['last_completed'],
            'totals' => $this->resource['totals'],
        ];
    }
}
