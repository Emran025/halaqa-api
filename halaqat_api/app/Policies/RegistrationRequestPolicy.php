<?php

namespace App\Policies;

use App\Models\RegistrationRequest;
use App\Models\User;

class RegistrationRequestPolicy
{
    public function view(User $user, RegistrationRequest $registrationRequest): bool
    {
        if ($registrationRequest->student_id === $user->id) {
            return true;
        }

        if (! $user->isTeacher()) {
            return false;
        }

        return $registrationRequest->teacher_id === $user->id
            || ($registrationRequest->routing_mode === 'all_available_teachers' && $registrationRequest->state === 'pending'
                && $registrationRequest->student()->where('gender', $user->gender)->where('country', $user->country)->exists())
            || $registrationRequest->requestedHalaqa()->where('teacher_id', $user->id)->exists();
    }

    public function decide(User $user, RegistrationRequest $registrationRequest): bool
    {
        if (! $user->isTeacher() || ! in_array($registrationRequest->state, ['pending', 'completion_requested'], true)) {
            return false;
        }

        return $registrationRequest->teacher_id === $user->id
            || ($registrationRequest->routing_mode === 'all_available_teachers'
                && $registrationRequest->student()->where('gender', $user->gender)->where('country', $user->country)->exists())
            || $registrationRequest->requestedHalaqa()->where('teacher_id', $user->id)->exists();
    }

    public function cancel(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $registrationRequest->student_id === $user->id
            && in_array($registrationRequest->state, ['pending', 'completion_requested'], true);
    }
}
