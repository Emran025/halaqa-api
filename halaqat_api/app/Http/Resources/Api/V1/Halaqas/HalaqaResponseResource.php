<?php

namespace App\Http\Resources\Api\V1\Halaqas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HalaqaResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['halaqa' => HalaqaResource::make($this->resource)];
    }
}
