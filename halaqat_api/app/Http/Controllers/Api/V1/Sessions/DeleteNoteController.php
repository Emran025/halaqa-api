<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Models\TaskNote;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DeleteNoteController extends Controller
{
    public function __invoke(LiveSession $session, SessionTask $task, TaskNote $note, SessionAnnotationService $service): Response
    {
        Gate::authorize('annotate', [$task, $session]);
        $service->deleteNote(request()->user(), $session, $task, $note);

        return response()->noContent();
    }
}
