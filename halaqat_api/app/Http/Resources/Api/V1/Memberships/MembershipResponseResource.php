<?php

namespace App\Http\Resources\Api\V1\Memberships;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['membership' => MembershipResource::make($this->resource)];
    }
}
