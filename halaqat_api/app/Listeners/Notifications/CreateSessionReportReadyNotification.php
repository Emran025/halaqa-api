<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\SessionReportApproved;
use App\Services\Notifications\NotificationService;

class CreateSessionReportReadyNotification
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(SessionReportApproved $event): void
    {
        $report = $event->report->loadMissing('session.student');
        $student = $report->session->student;
        $this->notifications->create(
            $student,
            'report_ready',
            'تقرير الجلسة جاهز',
            'اعتمد المعلم تقرير الجلسة ويمكنك مراجعته والإقرار به.',
            ['event_type' => 'report_ready', 'entity_type' => 'session_report', 'entity_id' => $report->id, 'session_id' => $report->session_id, 'action' => 'review', 'action_path' => '/sessions/'.$report->session_id.'/report'],
            'report_ready:'.$report->id.':'.$student->id,
        );
    }
}
