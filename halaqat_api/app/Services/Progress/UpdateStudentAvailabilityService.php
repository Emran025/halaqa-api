<?php

namespace App\Services\Progress;

use App\Exceptions\ApiConflictException;
use App\Models\StudentAvailabilityProfile;
use App\Models\StudentAvailabilitySlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateStudentAvailabilityService
{
    public function update(User $student, array $data): StudentAvailabilityProfile
    {
        return DB::transaction(function () use ($student, $data): StudentAvailabilityProfile {
            $slots = $data['weekly_slots'];
            foreach ($slots as $index => $slot) {
                foreach (array_slice($slots, $index + 1) as $other) {
                    if ((int) $slot['day_of_week'] === (int) $other['day_of_week'] && $slot['from'] < $other['to'] && $other['from'] < $slot['to']) {
                        throw new ApiConflictException('Weekly availability slots cannot overlap.', 'availability_overlap', 'user', $student->id);
                    }
                }
            }

            $profile = StudentAvailabilityProfile::query()->lockForUpdate()->firstOrCreate(
                ['student_id' => $student->id],
                ['timezone' => $data['timezone'], 'preferred_session_duration_minutes' => $data['preferred_session_duration_minutes'] ?? 30],
            );
            $profile->update(['timezone' => $data['timezone'], 'preferred_session_duration_minutes' => $data['preferred_session_duration_minutes'] ?? 30]);
            StudentAvailabilitySlot::query()->where('student_id', $student->id)->delete();
            foreach ($slots as $slot) {
                StudentAvailabilitySlot::create([
                    'student_id' => $student->id, 'day_of_week' => $slot['day_of_week'], 'available_from' => $slot['from'], 'available_to' => $slot['to'], 'is_preferred' => $slot['preferred'] ?? false,
                ]);
            }

            return $profile->load('slots');
        });
    }
}
