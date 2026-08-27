<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Sessions\MushafStateResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\SessionMushafStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GetMushafStateController extends Controller
{
    public function __invoke(LiveSession $session, SessionMushafStateService $service): JsonResponse
    {
        Gate::authorize('viewMushafState', $session);

        return MushafStateResponseResource::make($service->get($session))->response()->setStatusCode(200);
    }
}
