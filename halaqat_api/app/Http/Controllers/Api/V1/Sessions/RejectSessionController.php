<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\SessionDecisionRequest;
use App\Http\Resources\Api\V1\Sessions\SessionResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\SessionTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RejectSessionController extends Controller
{
    public function __invoke(SessionDecisionRequest $request, LiveSession $session, SessionTransitionService $service): JsonResponse
    {
        Gate::authorize('reject', $session);

        return SessionResponseResource::make($service->reject($session, $request->validated()['note'] ?? null))->response()->setStatusCode(200);
    }
}
