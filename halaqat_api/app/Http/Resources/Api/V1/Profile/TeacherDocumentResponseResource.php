<?php

namespace App\Http\Resources\Api\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherDocumentResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['teacher_document' => (new TeacherDocumentResource($this->resource))->resolve($request)];
    }
}
