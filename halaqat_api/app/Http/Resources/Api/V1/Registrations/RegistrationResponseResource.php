<?php

namespace App\Http\Resources\Api\V1\Registrations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['registration_request' => RegistrationRequestResource::make($this->resource)];
    }
}
