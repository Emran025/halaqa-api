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
            || ($registrationRequest->routing_mode === 'all_available_teachers'
                && in_array($registrationRequest->state, ['pending', 'completion_requested'], true)
                && $this->matchesApplicantSnapshot($user, $registrationRequest))
            || $registrationRequest->requestedHalaqa()->where('teacher_id', $user->id)->exists();
    }

    public function decide(User $user, RegistrationRequest $registrationRequest): bool
    {
        if (! $user->isTeacher() || ! in_array($registrationRequest->state, ['pending', 'completion_requested'], true)) {
            return false;
        }

        return $registrationRequest->teacher_id === $user->id
            || ($registrationRequest->routing_mode === 'all_available_teachers'
                && $this->matchesApplicantSnapshot($user, $registrationRequest))
            || $registrationRequest->requestedHalaqa()->where('teacher_id', $user->id)->exists();
    }

    private function matchesApplicantSnapshot(User $teacher, RegistrationRequest $request): bool
    {
        $profile = $request->relationLoaded('profile') ? $request->profile : $request->profile()->first();
        $student = $request->relationLoaded('student') ? $request->student : $request->student()->first();

        return $profile !== null
            ? $profile->gender === $teacher->gender && $profile->country === $teacher->country
            : $student?->gender === $teacher->gender && $student?->country === $teacher->country;
    }

    public function cancel(User $user, RegistrationRequest $registrationRequest): bool
    {
        return $registrationRequest->student_id === $user->id
            && in_array($registrationRequest->state, ['pending', 'completion_requested'], true);
    }
}
