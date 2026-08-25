<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Sessions\EvaluationResponseResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GetEvaluationController extends Controller
{
    public function __invoke(LiveSession $session, SessionTask $task, SessionAnnotationService $service): JsonResponse
    {
        Gate::authorize('annotate', [$task, $session]);

        return EvaluationResponseResource::make($service->listEvaluations($session, $task))->response()->setStatusCode(200);
    }
}
