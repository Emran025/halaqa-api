<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $evaluations = $this->resource;

        return ['teacher' => $this->item($evaluations['teacher'] ?? null), 'student' => $this->item($evaluations['student'] ?? null)];
    }

    private function item($evaluation): ?array
    {
        if ($evaluation === null) {
            return null;
        }

        return ['score' => (float) $evaluation->score, 'comment' => $evaluation->comment, 'evaluator' => ['id' => (string) $evaluation->evaluator->id, 'name' => $evaluation->evaluator->name, 'role' => $evaluation->evaluator->role], 'evaluator_role' => $evaluation->evaluator_role, 'updated_at' => $evaluation->updated_at?->toISOString()];
    }
}
