<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendancePreferencesResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['attendance_preferences' => AttendancePreferencesResource::make($this->resource)];
    }
}
