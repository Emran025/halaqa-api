<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Sessions\TaskCollectionResource;
use App\Models\LiveSession;
use App\Services\Sessions\SessionTaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ListTasksController extends Controller
{
    public function __invoke(Request $request, LiveSession $session, SessionTaskService $service): TaskCollectionResource
    {
        Gate::authorize('view', $session);

        return new TaskCollectionResource($service->list($session, min(max((int) $request->query('per_page', 20), 1), 100)));
    }
}
