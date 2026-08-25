<?php

namespace App\Services\Progress;

use App\Exceptions\ApiConflictException;
use App\Models\FollowUpPlan;
use App\Models\TrackingType;
use App\Models\TrackingUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FollowUpPlanService
{
    public function update(User $student, User $actor, array $data): FollowUpPlan
    {
        return DB::transaction(function () use ($student, $actor, $data): FollowUpPlan {
            $plan = FollowUpPlan::query()->where('student_id', $student->id)->latest('version')->lockForUpdate()->first();
            if ($actor->isStudent() && $plan?->status === 'active') {
                throw new ApiConflictException('An active plan can only be changed by the linked teacher.', 'active_plan_teacher_only', 'follow_up_plan', $plan->id);
            }

            $types = TrackingType::query()->pluck('id', 'code');
            $units = TrackingUnit::query()->pluck('id', 'code');
            foreach ($data['details'] as $detail) {
                if (! isset($types[$detail['task_type']], $units[$detail['unit']])) {
                    throw new ApiConflictException('The plan contains an inactive tracking type or unit.', 'invalid_tracking_reference', 'follow_up_plan', $plan?->id);
                }
            }

            if ($plan === null) {
                $plan = FollowUpPlan::create(['id' => (string) Str::uuid(), 'student_id' => $student->id, 'created_by_user_id' => $actor->id, 'frequency' => $data['frequency'], 'status' => $actor->isTeacher() ? 'active' : 'proposed', 'timezone' => $student->studentProfile->availability->timezone, 'starts_on' => $data['starts_on'] ?? null, 'ends_on' => $data['ends_on'] ?? null, 'version' => 1, 'approved_by_user_id' => $actor->isTeacher() ? $actor->id : null, 'approved_at' => $actor->isTeacher() ? now() : null]);
            } else {
                $plan->update(['created_by_user_id' => $actor->id, 'frequency' => $data['frequency'], 'status' => $actor->isTeacher() ? 'active' : 'proposed', 'starts_on' => $data['starts_on'] ?? null, 'ends_on' => $data['ends_on'] ?? null, 'version' => $plan->version + 1, 'approved_by_user_id' => $actor->isTeacher() ? $actor->id : null, 'approved_at' => $actor->isTeacher() ? now() : null]);
                $plan->details()->delete();
            }

            foreach ($data['details'] as $index => $detail) {
                $plan->details()->create(['id' => (string) Str::uuid(), 'tracking_type_id' => $types[$detail['task_type']], 'tracking_unit_id' => $units[$detail['unit']], 'amount' => $detail['amount'], 'notes' => $detail['notes'] ?? null, 'sort_order' => $index + 1]);
            }

            return $plan->fresh(['student.studentProfile.availability', 'details.trackingType', 'details.trackingUnit']);
        });
    }
}
