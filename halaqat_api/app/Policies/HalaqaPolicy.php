<?php

namespace App\Policies;

use App\Models\Halaqa;
use App\Models\User;

class HalaqaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher() && $user->isActive();
    }

    public function view(User $user, Halaqa $halaqa): bool
    {
        if ($user->isTeacher()) {
            return $halaqa->teacher_id === $user->id;
        }

        return $halaqa->status === 'active'
            && $halaqa->gender === $user->gender
            && $halaqa->country === $user->country
            && $halaqa->activeMemberships()->where('student_id', $user->id)->exists();
    }

    public function update(User $user, Halaqa $halaqa): bool
    {
        return $user->isTeacher() && $halaqa->teacher_id === $user->id;
    }

    public function manageMembers(User $user, Halaqa $halaqa): bool
    {
        return $this->update($user, $halaqa);
    }
}
