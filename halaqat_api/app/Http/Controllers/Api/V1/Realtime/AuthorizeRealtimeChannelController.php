<?php

namespace App\Http\Controllers\Api\V1\Realtime;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Realtime\AuthorizeRealtimeChannelRequest;
use App\Http\Resources\Api\V1\Realtime\RealtimeChannelAuthorizationResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\RealtimeSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AuthorizeRealtimeChannelController extends Controller
{
    public function __invoke(AuthorizeRealtimeChannelRequest $request, RealtimeSessionService $service): JsonResponse
    {
        $session = LiveSession::query()->whereKey($request->validated('session_id'))->firstOrFail();
        Gate::authorize('realtime', $session);

        return RealtimeChannelAuthorizationResponseResource::make($service->authorizeChannel($request->user(), $session->id, $request->validated('channel_name')))->response()->setStatusCode(200);
    }
}
