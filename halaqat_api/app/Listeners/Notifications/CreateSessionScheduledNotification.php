<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\SessionScheduled;
use App\Services\Notifications\NotificationService;

class CreateSessionScheduledNotification
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(SessionScheduled $event): void
    {
        $session = $event->session->loadMissing('student', 'taskType');
        $this->notifications->create(
            $session->student,
            'session_scheduled',
            'تمت جدولة جلسة جديدة',
            'لديك جلسة تحفيظ مجدولة مع المعلم.',
            ['event_type' => 'session_scheduled', 'entity_type' => 'live_session', 'entity_id' => $session->id, 'session_id' => $session->id, 'action' => 'open', 'action_path' => '/sessions/'.$session->id],
            'session_scheduled:'.$session->id.':'.$session->student_id,
        );
    }
}
