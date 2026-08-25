<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\UpdateMistakeRequest;
use App\Http\Resources\Api\V1\Sessions\AnnotationResponseResource;
use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\SessionTask;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UpdateMistakeController extends Controller
{
    public function __invoke(UpdateMistakeRequest $request, LiveSession $session, SessionTask $task, Mistake $mistake, SessionAnnotationService $service): JsonResponse
    {
        Gate::authorize('manage', [$mistake, $session, $task]);

        return AnnotationResponseResource::make($service->updateMistake($request->user(), $session, $task, $mistake, $request->validated()))->response()->setStatusCode(200);
    }
}
