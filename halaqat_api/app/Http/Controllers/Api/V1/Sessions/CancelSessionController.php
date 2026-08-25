<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Services\Sessions\SessionTransitionService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CancelSessionController extends Controller
{
    public function __invoke(LiveSession $session, SessionTransitionService $service): Response
    {
        Gate::authorize('cancel', $session);
        $service->cancel($session);

        return response()->noContent();
    }
}
