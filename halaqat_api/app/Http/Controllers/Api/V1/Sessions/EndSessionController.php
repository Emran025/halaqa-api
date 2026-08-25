<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Sessions\SessionResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\SessionTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EndSessionController extends Controller
{
    public function __invoke(LiveSession $session, SessionTransitionService $service): JsonResponse
    {
        Gate::authorize('end', $session);

        return SessionResponseResource::make($service->end($session))->response()->setStatusCode(200);
    }
}
