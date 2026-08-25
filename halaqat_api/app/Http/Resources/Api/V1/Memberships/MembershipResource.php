<?php

namespace App\Http\Resources\Api\V1\Memberships;

use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'halaqa_id' => (string) $this->halaqa_id,
            'student' => UserResource::make($this->student),
            'status' => $this->status,
            'joined_at' => $this->joined_at?->toISOString(),
        ];
    }
}
