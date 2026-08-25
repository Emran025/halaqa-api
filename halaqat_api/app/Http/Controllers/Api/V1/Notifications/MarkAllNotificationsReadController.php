<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Response;

class MarkAllNotificationsReadController extends Controller
{
    public function __invoke(NotificationService $service): Response
    {
        $service->markAllRead(request()->user());

        return response()->noContent();
    }
}
