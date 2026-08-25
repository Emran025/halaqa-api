<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\CreateNoteRequest;
use App\Http\Resources\Api\V1\Sessions\AnnotationResponseResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CreateNoteController extends Controller
{
    public function __invoke(CreateNoteRequest $request, LiveSession $session, SessionTask $task, SessionAnnotationService $service): JsonResponse
    {
        Gate::authorize('annotate', [$task, $session]);

        return AnnotationResponseResource::make($service->saveNote($request->user(), $session, $task, $request->validated()))->response()->setStatusCode(201);
    }
}
