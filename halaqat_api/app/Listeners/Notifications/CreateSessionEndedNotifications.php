<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\SessionEnded;
use App\Services\Notifications\NotificationService;

class CreateSessionEndedNotifications
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(SessionEnded $event): void
    {
        $session = $event->session->loadMissing('teacher', 'student');
        foreach ([$session->teacher, $session->student] as $recipient) {
            $this->notifications->create(
                $recipient,
                'session_ended',
                'انتهت الجلسة',
                'انتهت جلسة التحفيظ ويمكنك مراجعة تفاصيلها.',
                ['event_type' => 'session_ended', 'entity_type' => 'live_session', 'entity_id' => $session->id, 'session_id' => $session->id, 'action' => 'review', 'action_path' => '/sessions/'.$session->id],
                'session_ended:'.$session->id.':'.$recipient->id,
            );
        }
    }
}
