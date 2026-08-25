<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Sessions\TaskResponseResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Services\Sessions\SessionTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GetTaskController extends Controller
{
    public function __invoke(LiveSession $session, SessionTask $task, SessionTaskService $service): JsonResponse
    {
        Gate::authorize('view', [$task, $session]);

        return TaskResponseResource::make($service->get($session, $task))->response()->setStatusCode(200);
    }
}
