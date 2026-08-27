<?php

namespace App\Services\Profile;

use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\Progress\FollowUpPlanService;
use App\Services\Progress\UpdateStudentAvailabilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        if ($user->isTeacher() && array_intersect(array_keys($data), ['memorization_level', 'review_level']) !== []) {
            throw new AuthorizationException('Only students can update memorization levels.');
        }

        return DB::transaction(function () use ($user, $data): User {
            $user->update(array_intersect_key($data, array_flip([
                'name',
                'phone',
            ])));

            if ($user->isStudent() && $user->studentProfile !== null) {
                $user->studentProfile->update(array_intersect_key($data, array_flip([
                    'memorization_level',
                    'review_level',
                ])));
            }

            return $user->fresh(['studentProfile', 'teacherProfile']);
        });
    }

    public function updateStudent(User $student, array $data): User
    {
        return DB::transaction(function () use ($student, $data): User {
            $student->update(array_intersect_key($data, array_flip([
                'name', 'birth_date', 'gender', 'country', 'city', 'residence',
                'phone', 'phone_zone', 'whatsapp_phone', 'whatsapp_zone',
            ])));

            $profile = StudentProfile::query()->firstOrCreate(['user_id' => $student->id]);
            $profileData = array_intersect_key($data, array_flip([
                'memorization_level', 'review_level', 'bio',
            ]));

            if (array_key_exists('previous_memorization', $data) && is_array($data['previous_memorization'])) {
                $previous = $data['previous_memorization'];
                foreach ([
                    'memorization_level', 'review_level', 'memorized_juz_count',
                    'memorized_surah_ids', 'last_completed_unit', 'previous_teacher_notes', 'stop_reasons',
                ] as $field) {
                    if (array_key_exists($field, $previous)) {
                        $profileData[match ($field) {
                            'previous_teacher_notes' => 'previous_memorization_notes',
                            default => $field,
                        }] = $previous[$field];
                    }
                }

                if (! array_key_exists('memorization_level', $data) && array_key_exists('memorization_level', $previous)) {
                    $profileData['memorization_level'] = $previous['memorization_level'];
                }
                if (! array_key_exists('review_level', $data) && array_key_exists('review_level', $previous)) {
                    $profileData['review_level'] = $previous['review_level'];
                }
            }
            if ($profileData !== []) {
                $profile->update($profileData);
            }

            if (array_key_exists('attendance_preferences', $data)) {
                app(UpdateStudentAvailabilityService::class)->update($student, $data['attendance_preferences']);
            }

            if (array_key_exists('follow_up_plan', $data)) {
                $student->load('studentProfile.availability');
                app(FollowUpPlanService::class)->update($student, $student, $data['follow_up_plan']);
            }

            return $student->fresh([
                'studentProfile.availability.slots',
                'studentProfile.followUpPlan.details.trackingType',
                'studentProfile.followUpPlan.details.trackingUnit',
            ]);
        });
    }

    public function updateTeacher(User $teacher, array $data): User
    {
        return DB::transaction(function () use ($teacher, $data): User {
            $teacher->update(array_intersect_key($data, array_flip([
                'name', 'birth_date', 'gender', 'country', 'city', 'residence',
                'phone', 'phone_zone', 'whatsapp_phone', 'whatsapp_zone',
            ])));

            $profile = TeacherProfile::query()->firstOrCreate(['user_id' => $teacher->id]);
            $profile->update(array_intersect_key($data, array_flip([
                'qualification', 'experience_years', 'available_time', 'bio', 'max_halaqas',
            ])));

            return $teacher->fresh([
                'teacherProfile.documents',
                'halaqas' => fn ($query) => $query->where('status', 'active')->withCount('activeMemberships'),
            ])->loadCount(['halaqas as active_halaqas_count' => fn ($query) => $query->where('status', 'active')]);
        });
    }
}
