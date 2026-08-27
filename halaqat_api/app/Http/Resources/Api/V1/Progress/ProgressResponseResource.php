<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['progress' => (new ProgressResource($this->resource))->resolve($request)];
    }
}
