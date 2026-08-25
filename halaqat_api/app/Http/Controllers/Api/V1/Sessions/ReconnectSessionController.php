<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Realtime\RealtimeSessionResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\RealtimeSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReconnectSessionController extends Controller
{
    public function __invoke(LiveSession $session, RealtimeSessionService $service): JsonResponse
    {
        Gate::authorize('reconnect', $session);

        return RealtimeSessionResponseResource::make($service->reconnect($session))->response()->setStatusCode(200);
    }
}
