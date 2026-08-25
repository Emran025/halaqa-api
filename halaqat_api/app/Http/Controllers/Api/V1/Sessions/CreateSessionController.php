<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\CreateSessionRequest;
use App\Http\Resources\Api\V1\Sessions\SessionResponseResource;
use App\Services\Sessions\LiveSessionService;
use Illuminate\Http\JsonResponse;

class CreateSessionController extends Controller
{
    public function __invoke(CreateSessionRequest $request, LiveSessionService $service): JsonResponse
    {
        return SessionResponseResource::make($service->create($request->user(), $request->validated()))->response()->setStatusCode(201);
    }
}
