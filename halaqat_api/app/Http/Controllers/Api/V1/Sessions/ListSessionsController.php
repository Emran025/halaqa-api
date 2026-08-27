<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\ListSessionsRequest;
use App\Http\Resources\Api\V1\Sessions\SessionCollectionResource;
use App\Services\Sessions\SessionQueryService;

class ListSessionsController extends Controller
{
    public function __invoke(ListSessionsRequest $request, SessionQueryService $service): SessionCollectionResource
    {
        return new SessionCollectionResource($service->listFor($request->user(), $request->validated()));
    }
}
