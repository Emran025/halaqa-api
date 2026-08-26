<?php

namespace App\Services\Progress;

use App\Models\FollowUpItem;
use App\Models\FollowUpPlan;
use App\Models\HalaqaMembership;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FollowUpAutomationService
{
    public function process(?Carbon $now = null): int
    {
        $now ??= now();
        $created = $this->generateNextItems($now);
        $this->markDueItems($now);

        return $created;
    }

    private function generateNextItems(Carbon $now): int
    {
        $created = 0;
        FollowUpPlan::query()
            ->where('status', 'active')
            ->with(['details', 'student'])
            ->chunkById(100, function ($plans) use ($now, &$created): void {
                foreach ($plans as $plan) {
                    $timezone = $plan->timezone ?: 'UTC';
                    $localNow = $now->copy()->setTimezone($timezone);
                    $start = $plan->starts_on?->copy()->startOfDay() ?? $localNow->copy()->startOfDay();
                    $candidate = $start->greaterThan($localNow->startOfDay()) ? $start : $localNow->copy()->startOfDay();
                    $interval = match ($plan->frequency) {
                        'daily' => 1,
                        'onceAWeek' => 7,
                        'twiceAWeek' => 3,
                        'thriceAWeek' => 2,
                        default => 7,
                    };
                    $scheduled = $candidate->setTime(9, 0);
                    if ($scheduled->lessThanOrEqualTo($localNow)) {
                        $scheduled->addDays($interval);
                    }
                    if ($plan->ends_on !== null && $scheduled->toDateString() > $plan->ends_on->toDateString()) {
                        continue;
                    }

                    foreach ($plan->details as $detail) {
                        $exists = FollowUpItem::query()
                            ->where('plan_id', $plan->id)
                            ->where('plan_detail_id', $detail->id)
                            ->whereIn('state', ['upcoming', 'due', 'in_progress'])
                            ->where('scheduled_for', '>=', $now->copy()->subDay())
                            ->exists();
                        if ($exists) {
                            continue;
                        }

                        FollowUpItem::create([
                            'id' => (string) Str::uuid(),
                            'plan_id' => $plan->id,
                            'plan_detail_id' => $detail->id,
                            'student_id' => $plan->student_id,
                            'halaqa_id' => HalaqaMembership::query()->where('student_id', $plan->student_id)->where('status', 'active')->value('halaqa_id'),
                            'scheduled_for' => $scheduled->copy()->setTimezone('UTC'),
                            'timezone' => $timezone,
                            'state' => 'upcoming',
                        ]);
                        $created++;
                    }
                }
            }, 'id');

        return $created;
    }

    private function markDueItems(Carbon $now): void
    {
        FollowUpItem::query()
            ->whereIn('state', ['upcoming', 'due'])
            ->where('scheduled_for', '<=', $now)
            ->with(['student', 'halaqa.teacher'])
            ->chunkById(100, function ($items) use ($now): void {
                foreach ($items as $item) {
                    DB::transaction(function () use ($item, $now): void {
                        $locked = FollowUpItem::query()->lockForUpdate()->find($item->id);
                        if ($locked === null || ! in_array($locked->state, ['upcoming', 'due'], true) || $locked->scheduled_for->greaterThan($now)) {
                            return;
                        }
                        $locked->update(['state' => $locked->scheduled_for->lessThan($now->copy()->subDay()) ? 'overdue' : 'due']);
                        if ($locked->notification_sent_at !== null) {
                            return;
                        }
                        $this->notify($locked, (string) $locked->student_id, 'follow_up_due_student');
                        $teacherId = $locked->halaqa?->teacher_id;
                        if ($teacherId !== null) {
                            $this->notify($locked, (string) $teacherId, 'follow_up_due_teacher');
                        }
                        $locked->update(['notification_sent_at' => now()]);
                    });
                }
            }, 'id');
    }

    private function notify(FollowUpItem $item, string $userId, string $audience): void
    {
        Notification::firstOrCreate(
            ['dedupe_key' => 'follow-up-due:'.$audience.':'.$item->id],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'type' => 'follow_up_due',
                'title' => 'Follow-up item due',
                'body' => 'A scheduled Quran follow-up item is due.',
                'payload' => ['entity_type' => 'follow_up_item', 'entity_id' => (string) $item->id, 'action' => 'view'],
            ],
        );
    }
}
