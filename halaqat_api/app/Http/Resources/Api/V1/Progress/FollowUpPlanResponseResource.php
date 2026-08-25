<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpPlanResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['follow_up_plan' => FollowUpPlanResource::make($this->resource)];
    }
}
