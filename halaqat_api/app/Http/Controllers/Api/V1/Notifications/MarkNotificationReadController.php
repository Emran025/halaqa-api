<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MarkNotificationReadController extends Controller
{
    public function __invoke(Notification $notification, NotificationService $service): Response
    {
        abort_unless(Gate::allows('markRead', $notification), 404);
        $service->markRead(request()->user(), $notification);

        return response()->noContent();
    }
}
