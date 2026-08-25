<?php

namespace App\Policies;

use App\Models\HalaqaMembership;
use App\Models\User;

class HalaqaMembershipPolicy
{
    public function view(User $user, HalaqaMembership $membership): bool
    {
        return $membership->student_id === $user->id || $membership->halaqa()->where('teacher_id', $user->id)->exists();
    }

    public function update(User $user, HalaqaMembership $membership): bool
    {
        return $user->isTeacher() && $membership->halaqa()->where('teacher_id', $user->id)->exists();
    }

    public function delete(User $user, HalaqaMembership $membership): bool
    {
        return $this->update($user, $membership);
    }
}
