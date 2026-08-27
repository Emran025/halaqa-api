<?php

namespace App\Http\Resources\Api\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['student_profile' => (new StudentProfileResource($this->resource))->resolve($request)];
    }
}
