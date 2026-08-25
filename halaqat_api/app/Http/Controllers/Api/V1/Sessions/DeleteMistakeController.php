<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\SessionTask;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DeleteMistakeController extends Controller
{
    public function __invoke(LiveSession $session, SessionTask $task, Mistake $mistake, SessionAnnotationService $service): Response
    {
        Gate::authorize('manage', [$mistake, $session, $task]);
        $service->deleteMistake(request()->user(), $session, $task, $mistake);

        return response()->noContent();
    }
}
