<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\UpdateNoteRequest;
use App\Http\Resources\Api\V1\Sessions\AnnotationResponseResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Models\TaskNote;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UpdateNoteController extends Controller
{
    public function __invoke(UpdateNoteRequest $request, LiveSession $session, SessionTask $task, TaskNote $note, SessionAnnotationService $service): JsonResponse
    {
        Gate::authorize('annotate', [$task, $session]);

        return AnnotationResponseResource::make($service->updateNote($request->user(), $session, $task, $note, $request->validated()))->response()->setStatusCode(200);
    }
}
