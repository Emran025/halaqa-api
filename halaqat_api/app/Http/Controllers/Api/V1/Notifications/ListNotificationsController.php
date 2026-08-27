<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\ListNotificationsRequest;
use App\Http\Resources\Api\V1\Notifications\NotificationCollectionResource;
use App\Models\Notification;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Gate;

class ListNotificationsController extends Controller
{
    public function __invoke(ListNotificationsRequest $request, NotificationService $service): NotificationCollectionResource
    {
        Gate::authorize('viewAny', Notification::class);

        return new NotificationCollectionResource($service->list($request->user(), $request->validated()));
    }
}
