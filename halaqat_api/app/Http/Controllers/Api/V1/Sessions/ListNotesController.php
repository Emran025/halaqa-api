<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\ListNotesRequest;
use App\Http\Resources\Api\V1\Sessions\NoteCollectionResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Support\Facades\Gate;

class ListNotesController extends Controller
{
    public function __invoke(ListNotesRequest $request, LiveSession $session, SessionTask $task, SessionAnnotationService $service): NoteCollectionResource
    {
        Gate::authorize('annotate', [$task, $session]);

        return new NoteCollectionResource($service->listNotes($session, $task, (int) ($request->validated()['per_page'] ?? 20)));
    }
}
