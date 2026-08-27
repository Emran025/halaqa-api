<?php

namespace App\Http\Controllers\Api\V1\Progress;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Progress\ListFollowUpItemsRequest;
use App\Http\Resources\Api\V1\Progress\FollowUpItemCollectionResource;
use App\Models\FollowUpItem;
use App\Services\Progress\FollowUpItemService;
use Illuminate\Support\Facades\Gate;

class ListFollowUpItemsController extends Controller
{
    public function __invoke(ListFollowUpItemsRequest $request, FollowUpItemService $service): FollowUpItemCollectionResource
    {
        Gate::authorize('viewAny', FollowUpItem::class);

        return new FollowUpItemCollectionResource($service->list($request->user(), $request->validated()));
    }
}
