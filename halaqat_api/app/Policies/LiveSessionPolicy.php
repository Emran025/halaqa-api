<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    public function view(User $user, LiveSession $session): bool
    {
        return $this->isParticipant($user, $session);
    }

    public function cancel(User $user, LiveSession $session): bool
    {
        return $session->teacher_id === $user->id && $session->state === 'requested';
    }

    public function accept(User $user, LiveSession $session): bool
    {
        return $session->student_id === $user->id && $session->state === 'requested';
    }

    public function reject(User $user, LiveSession $session): bool
    {
        return $session->student_id === $user->id && $session->state === 'requested';
    }

    public function leave(User $user, LiveSession $session): bool
    {
        return $this->isParticipant($user, $session) && ! in_array($session->state, ['ended', 'cancelled', 'rejected'], true);
    }

    public function end(User $user, LiveSession $session): bool
    {
        return $session->teacher_id === $user->id && ! in_array($session->state, ['ended', 'cancelled', 'rejected'], true);
    }

    public function viewMushafState(User $user, LiveSession $session): bool
    {
        return $this->isParticipant($user, $session);
    }

    public function updateMushafState(User $user, LiveSession $session): bool
    {
        return $this->isParticipant($user, $session) && ! in_array($session->state, ['cancelled', 'rejected'], true);
    }

    private function isParticipant(User $user, LiveSession $session): bool
    {
        return $session->teacher_id === $user->id || $session->student_id === $user->id;
    }
}
