<?php

namespace App\Policies;

use App\Models\FollowUpItem;
use App\Models\HalaqaMembership;
use App\Models\User;

class FollowUpItemPolicy
{
    public function viewAny(User $viewer): bool
    {
        return $viewer->isTeacher() || $viewer->isStudent();
    }

    public function view(User $viewer, FollowUpItem $item): bool
    {
        return $this->canAccess($viewer, $item);
    }

    public function complete(User $viewer, FollowUpItem $item): bool
    {
        return $this->canAccess($viewer, $item);
    }

    public function skip(User $viewer, FollowUpItem $item): bool
    {
        return $this->canAccess($viewer, $item);
    }

    public function reschedule(User $viewer, FollowUpItem $item): bool
    {
        return $this->canAccess($viewer, $item);
    }

    private function canAccess(User $viewer, FollowUpItem $item): bool
    {
        if ($viewer->isStudent()) {
            return (string) $item->student_id === (string) $viewer->id;
        }

        return $viewer->isTeacher()
            && $item->halaqa_id !== null
            && HalaqaMembership::query()
                ->where('halaqa_id', $item->halaqa_id)
                ->where('student_id', $item->student_id)
                ->where('status', 'active')
                ->whereHas('halaqa', fn ($query) => $query->where('teacher_id', $viewer->id))
                ->exists();
    }
}
