<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\CreateTaskRequest;
use App\Http\Resources\Api\V1\Sessions\TaskResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\LiveSessionService;
use Illuminate\Http\JsonResponse;

class CreateTaskController extends Controller
{
    public function __invoke(CreateTaskRequest $request, LiveSession $session, LiveSessionService $service): JsonResponse
    {
        return TaskResponseResource::make($service->createTask($request->user(), $session, $request->validated()))->response()->setStatusCode(201);
    }
}
