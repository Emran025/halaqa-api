<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResponseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this['user']),
            'token' => $this['token'],
            'token_type' => 'Bearer',
            'expires_at' => $this['expires_at'],
        ];
    }
}
