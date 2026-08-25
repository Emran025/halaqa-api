<?php

namespace App\Policies;

use App\Models\HalaqaMembership;
use App\Models\User;

class StudentLearningPolicy
{
    public function view(User $viewer, User $student): bool
    {
        if ($viewer->id === $student->id) {
            return true;
        }

        return $viewer->isTeacher() && HalaqaMembership::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('halaqa', fn ($query) => $query->where('teacher_id', $viewer->id))
            ->exists();
    }

    public function update(User $viewer, User $student): bool
    {
        return $this->view($viewer, $student);
    }
}
