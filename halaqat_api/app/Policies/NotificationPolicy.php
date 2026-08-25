<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $viewer): bool
    {
        return $viewer->isTeacher() || $viewer->isStudent();
    }

    public function view(User $viewer, Notification $notification): bool
    {
        return (string) $notification->user_id === (string) $viewer->id;
    }

    public function markRead(User $viewer, Notification $notification): bool
    {
        return $this->view($viewer, $notification);
    }
}
