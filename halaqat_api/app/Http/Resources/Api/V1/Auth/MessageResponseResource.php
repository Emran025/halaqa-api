<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResponseResource extends JsonResource
{
    /** @return array{message: string} */
    public function toArray(Request $request): array
    {
        return [
            'message' => $this['message'],
        ];
    }
}
