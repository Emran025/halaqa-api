<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\UpdateTaskRequest;
use App\Http\Resources\Api\V1\Sessions\TaskResponseResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Services\Sessions\SessionTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UpdateTaskController extends Controller
{
    public function __invoke(UpdateTaskRequest $request, LiveSession $session, SessionTask $task, SessionTaskService $service): JsonResponse
    {
        Gate::authorize('update', [$task, $session]);

        return TaskResponseResource::make($service->update($request->user(), $session, $task, $request->validated()))->response()->setStatusCode(200);
    }
}
