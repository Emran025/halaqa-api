<?php

namespace App\Http\Controllers\Api\V1\Sessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sessions\ListMistakesRequest;
use App\Http\Resources\Api\V1\Sessions\MistakeCollectionResource;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Services\Sessions\SessionAnnotationService;
use Illuminate\Support\Facades\Gate;

class ListMistakesController extends Controller
{
    public function __invoke(ListMistakesRequest $request, LiveSession $session, SessionTask $task, SessionAnnotationService $service): MistakeCollectionResource
    {
        Gate::authorize('annotate', [$task, $session]);

        return new MistakeCollectionResource($service->listMistakes($session, $task, (int) ($request->validated()['per_page'] ?? 20)));
    }
}
