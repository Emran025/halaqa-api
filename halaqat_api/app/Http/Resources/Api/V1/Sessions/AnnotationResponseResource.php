<?php

namespace App\Http\Resources\Api\V1\Sessions;

use App\Models\Mistake;
use App\Models\TaskNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnotationResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Mistake) {
            return ['mistake' => ['id' => (string) $this->id, 'session_id' => (string) $this->detail?->task?->session_id, 'task_id' => (string) $this->detail?->session_task_id, 'ayah_id' => (int) $this->ayah_id, 'page_number' => $this->ayah?->page_number === null ? null : (int) $this->ayah->page_number,
                'word_index' => (int) $this->word_index, 'mistake_type' => $this->mistakeType?->code, 'source' => $this->source_role, 'note' => $this->note, 'created_by' => ['id' => (string) $this->creator->id, 'name' => $this->creator->name, 'role' => $this->creator->role], 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()]];
        }
        if ($this->resource instanceof TaskNote) {
            return ['note' => ['id' => (string) $this->id, 'body' => $this->note, 'author' => ['id' => (string) $this->author->id, 'name' => $this->author->name, 'role' => $this->author->role], 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()]];
        }

        return ['evaluation' => ['score' => (float) $this->score, 'comment' => $this->comment, 'evaluator' => ['id' => (string) $this->evaluator->id, 'name' => $this->evaluator->name, 'role' => $this->evaluator->role], 'evaluator_role' => $this->evaluator_role]];
    }
}
