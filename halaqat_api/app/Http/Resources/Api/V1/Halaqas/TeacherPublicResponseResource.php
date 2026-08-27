<?php

namespace App\Http\Resources\Api\V1\Halaqas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherPublicResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['teacher' => (new TeacherPublicResource($this->resource))->resolve($request)];
    }
}
