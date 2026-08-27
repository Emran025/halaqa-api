<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Progress\CompleteFollowUpItemRequest;
use App\Http\Requests\Api\V1\Progress\RescheduleFollowUpItemRequest;
use App\Http\Requests\Api\V1\Progress\SkipFollowUpItemRequest;
use App\Http\Resources\Api\V1\Progress\FollowUpItemResponseResource;
use App\Models\FollowUpItem;
use App\Services\Progress\FollowUpItemService;
use Illuminate\Support\Facades\Gate;

class FollowUpItemActionController extends Controller
{
    public function complete(CompleteFollowUpItemRequest $request, FollowUpItem $followUpItem, FollowUpItemService $service): FollowUpItemResponseResource
    {
        Gate::authorize('complete', $followUpItem);

        return new FollowUpItemResponseResource($service->complete($request->user(), $followUpItem, $request->validated('client_operation_id')));
    }

    public function skip(SkipFollowUpItemRequest $request, FollowUpItem $followUpItem, FollowUpItemService $service): FollowUpItemResponseResource
    {
        Gate::authorize('skip', $followUpItem);
        $data = $request->validated();

        return new FollowUpItemResponseResource($service->skip($request->user(), $followUpItem, $data['reason'], $data['client_operation_id']));
    }

    public function reschedule(RescheduleFollowUpItemRequest $request, FollowUpItem $followUpItem, FollowUpItemService $service): FollowUpItemResponseResource
    {
        Gate::authorize('reschedule', $followUpItem);

        return new FollowUpItemResponseResource($service->reschedule($request->user(), $followUpItem, $request->validated()));
    }
}
