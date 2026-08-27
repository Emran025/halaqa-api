<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\UpdateMushafStateRequest;
use App\Http\Resources\Api\V1\Sessions\MushafStateResponseResource;
use App\Models\LiveSession;
use App\Services\Sessions\SessionMushafStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SaveMushafStateController extends Controller
{
    public function __invoke(UpdateMushafStateRequest $request, LiveSession $session, SessionMushafStateService $service): JsonResponse
    {
        Gate::authorize('updateMushafState', $session);

        return MushafStateResponseResource::make($service->save($request->user(), $session, $request->validated()))->response()->setStatusCode(200);
    }
}
