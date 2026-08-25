<?php

namespace App\Services\Memberships;

use App\Exceptions\ApiConflictException;
use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MembershipService
{
    public function assign(Halaqa $halaqa, User $student): HalaqaMembership
    {
        return DB::transaction(function () use ($halaqa, $student): HalaqaMembership {
            $halaqa = Halaqa::query()->lockForUpdate()->findOrFail($halaqa->id);
            if (! $student->isStudent() || ! $student->isActive()) {
                throw new ApiConflictException('Only an active student can be assigned.', 'student_not_assignable', 'user', $student->id);
            }
            if ($halaqa->status !== 'active') {
                throw new ApiConflictException('Students cannot be assigned to an inactive halaqa.', 'halaqa_inactive', 'halaqa', $halaqa->id);
            }
            if ($halaqa->gender !== $student->gender) {
                throw new ApiConflictException('The student gender does not match the halaqa.', 'halaqa_gender_mismatch', 'halaqa', $halaqa->id);
            }
            if ($halaqa->max_students !== null && $halaqa->activeMemberships()->count() >= $halaqa->max_students) {
                throw new ApiConflictException('The halaqa has reached its student capacity.', 'halaqa_capacity_reached', 'halaqa', $halaqa->id);
            }
            if (HalaqaMembership::query()->where('student_id', $student->id)->where('status', 'active')->exists()) {
                throw new ApiConflictException('The student already belongs to an active halaqa.', 'student_already_assigned', 'user', $student->id);
            }

            $membership = HalaqaMembership::create([
                'id' => (string) Str::uuid(),
                'halaqa_id' => $halaqa->id,
                'student_id' => $student->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $membership->load('student');
        });
    }

    public function update(HalaqaMembership $membership, array $data): HalaqaMembership
    {
        return DB::transaction(function () use ($membership, $data): HalaqaMembership {
            $membership = HalaqaMembership::query()->lockForUpdate()->findOrFail($membership->id);
            $status = $data['status'];
            if ($membership->status === $status) {
                throw new ApiConflictException('The membership already has the requested status.', 'membership_state_unchanged', 'membership', $membership->id);
            }
            if ($status === 'active') {
                $halaqa = Halaqa::query()->lockForUpdate()->findOrFail($membership->halaqa_id);
                if ($halaqa->status !== 'active') {
                    throw new ApiConflictException('An inactive halaqa cannot have an active membership.', 'halaqa_inactive', 'halaqa', $halaqa->id);
                }
                if ($halaqa->max_students !== null && $halaqa->activeMemberships()->count() >= $halaqa->max_students) {
                    throw new ApiConflictException('The halaqa has reached its student capacity.', 'halaqa_capacity_reached', 'halaqa', $halaqa->id);
                }
                if (HalaqaMembership::query()->where('student_id', $membership->student_id)->where('status', 'active')->where('id', '!=', $membership->id)->exists()) {
                    throw new ApiConflictException('The student already belongs to an active halaqa.', 'student_already_assigned', 'user', $membership->student_id);
                }
                $membership->joined_at = now();
                $membership->left_at = null;
            } elseif ($status === 'removed') {
                $membership->left_at = now();
            }

            $membership->status = $status;
            $membership->save();

            return $membership->load('student');
        });
    }
}
