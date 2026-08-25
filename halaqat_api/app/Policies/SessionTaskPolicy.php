<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Models\User;

class SessionTaskPolicy
{
    public function annotate(User $user, SessionTask $task, LiveSession $session): bool
    {
        if ((string) $task->session_id !== (string) $session->id) {
            return false;
        }

        return $user->id === $session->teacher_id || $user->id === $session->student_id;
    }
}
