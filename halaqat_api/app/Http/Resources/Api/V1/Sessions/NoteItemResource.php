<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => (string) $this->id, 'body' => $this->note, 'author' => ['id' => (string) $this->author->id, 'name' => $this->author->name, 'role' => $this->author->role], 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
