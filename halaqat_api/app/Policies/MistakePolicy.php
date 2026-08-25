<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\SessionTask;
use App\Models\User;

class MistakePolicy
{
    public function manage(User $user, Mistake $mistake, LiveSession $session, SessionTask $task): bool
    {
        if ((string) $task->session_id !== (string) $session->id || (string) $mistake->detail?->session_task_id !== (string) $task->id) {
            return false;
        }

        return $user->id === $session->teacher_id || $user->id === $session->student_id;
    }
}
