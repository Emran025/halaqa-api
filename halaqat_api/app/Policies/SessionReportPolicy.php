<?php

namespace App\Policies;

use App\Models\SessionReport;
use App\Models\User;

class SessionReportPolicy
{
    public function view(User $viewer, SessionReport $report): bool
    {
        return $this->isParticipant($viewer, $report);
    }

    public function update(User $viewer, SessionReport $report): bool
    {
        return $this->isTeacherParticipant($viewer, $report);
    }

    public function approve(User $viewer, SessionReport $report): bool
    {
        return $this->isTeacherParticipant($viewer, $report);
    }

    public function acknowledge(User $viewer, SessionReport $report): bool
    {
        return $this->isStudentParticipant($viewer, $report);
    }

    public function reopen(User $viewer, SessionReport $report): bool
    {
        return $this->isTeacherParticipant($viewer, $report);
    }

    private function isParticipant(User $viewer, SessionReport $report): bool
    {
        $session = $report->relationLoaded('session') ? $report->session : $report->session()->first();

        return $session !== null && ($session->teacher_id === $viewer->id || $session->student_id === $viewer->id);
    }

    private function isTeacherParticipant(User $viewer, SessionReport $report): bool
    {
        $session = $report->relationLoaded('session') ? $report->session : $report->session()->first();

        return $viewer->isTeacher() && $session !== null && $session->teacher_id === $viewer->id;
    }

    private function isStudentParticipant(User $viewer, SessionReport $report): bool
    {
        $session = $report->relationLoaded('session') ? $report->session : $report->session()->first();

        return $viewer->isStudent() && $session !== null && $session->student_id === $viewer->id;
    }
}
