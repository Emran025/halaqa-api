<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\MarkDirectConnectionUnavailableRequest;
use App\Http\Resources\Api\V1\Sessions\SessionResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\SessionTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MarkDirectConnectionUnavailableController extends Controller
{
    public function __invoke(MarkDirectConnectionUnavailableRequest $request, LiveSession $session, SessionTransitionService $service): JsonResponse
    {
        Gate::authorize('markDirectConnectionUnavailable', $session);

        return SessionResponseResource::make($service->markDirectConnectionUnavailable($request->user(), $session, $request->validated('reason'), $request->validated('client_operation_id')))->response()->setStatusCode(200);
    }
}
