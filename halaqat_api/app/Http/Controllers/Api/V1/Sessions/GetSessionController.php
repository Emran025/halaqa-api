<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Sessions\SessionResponseResource;
use App\Models\LiveSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class GetSessionController extends Controller
{
    public function __invoke(LiveSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        return SessionResponseResource::make($session->load(['teacher', 'student', 'taskType']))->response()->setStatusCode(200);
    }
}
