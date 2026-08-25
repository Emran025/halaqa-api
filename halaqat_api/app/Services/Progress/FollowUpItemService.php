<?php

namespace App\Services\Progress;

use App\Exceptions\ApiConflictException;
use App\Models\FollowUpItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FollowUpItemService
{
    public function list(User $actor, array $filters): LengthAwarePaginator
    {
        $query = FollowUpItem::query()
            ->with(['detail.trackingType', 'detail.trackingUnit'])
            ->when($actor->isStudent(), fn ($builder) => $builder->where('student_id', $actor->id))
            ->when($actor->isTeacher(), fn ($builder) => $builder
                ->whereNotNull('halaqa_id')
                ->whereHas('halaqa', fn ($halaqa) => $halaqa->where('teacher_id', $actor->id))
                ->whereHas('halaqa.memberships', fn ($membership) => $membership
                    ->whereColumn('halaqa_memberships.student_id', 'follow_up_items.student_id')
                    ->where('halaqa_memberships.status', 'active')))
            ->when($filters['date'] ?? null, fn ($builder, $date) => $builder->whereDate('scheduled_for', $date))
            ->when($filters['state'] ?? null, fn ($builder, $state) => $builder->where('state', $state))
            ->when($filters['student_id'] ?? null, fn ($builder, $studentId) => $builder->where('student_id', $studentId))
            ->when($filters['task_type'] ?? null, fn ($builder, $taskType) => $builder->whereHas('detail.trackingType', fn ($type) => $type->where('code', $taskType)))
            ->orderBy('scheduled_for')
            ->orderBy('id');

        return $query->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function complete(User $actor, FollowUpItem $item, string $operationId): FollowUpItem
    {
        return DB::transaction(function () use ($actor, $item, $operationId): FollowUpItem {
            $locked = $this->lock($item);
            $replay = $this->assertOperation($locked, $actor, $operationId, 'complete');
            if ($replay !== null) {
                return $replay;
            }
            $this->assertPending($locked);
            $locked->update(['state' => 'completed', 'completed_at' => now(), 'skipped_at' => null, 'skip_reason' => null, 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'complete']);

            return $this->load($locked);
        });
    }

    public function skip(User $actor, FollowUpItem $item, string $reason, string $operationId): FollowUpItem
    {
        return DB::transaction(function () use ($actor, $item, $reason, $operationId): FollowUpItem {
            $locked = $this->lock($item);
            $replay = $this->assertOperation($locked, $actor, $operationId, 'skip');
            if ($replay !== null) {
                return $replay;
            }
            $this->assertPending($locked);
            $locked->update(['state' => 'skipped', 'completed_at' => null, 'skipped_at' => now(), 'skip_reason' => $reason, 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'skip']);

            return $this->load($locked);
        });
    }

    public function reschedule(User $actor, FollowUpItem $item, array $data): FollowUpItem
    {
        return DB::transaction(function () use ($actor, $item, $data): FollowUpItem {
            $locked = $this->lock($item);
            $operationId = $data['client_operation_id'];
            $replay = $this->assertOperation($locked, $actor, $operationId, 'reschedule');
            if ($replay !== null) {
                return $this->load($replay);
            }
            $this->assertPending($locked);
            $scheduledAt = Carbon::parse($data['scheduled_at'])->utc();
            $newItem = FollowUpItem::create(['id' => (string) Str::uuid(), 'plan_id' => $locked->plan_id, 'plan_detail_id' => $locked->plan_detail_id, 'student_id' => $locked->student_id, 'halaqa_id' => $locked->halaqa_id, 'scheduled_for' => $scheduledAt, 'timezone' => $data['timezone'] ?? $locked->timezone, 'state' => 'upcoming', 'rescheduled_from_id' => $locked->id, 'reschedule_reason' => $data['reason'] ?? null]);
            $locked->update(['state' => 'skipped', 'skipped_at' => now(), 'skip_reason' => $data['reason'] ?? 'Rescheduled', 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'reschedule']);

            return $this->load($newItem);
        });
    }

    private function lock(FollowUpItem $item): FollowUpItem
    {
        return FollowUpItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
    }

    private function load(FollowUpItem $item): FollowUpItem
    {
        return $item->fresh(['detail.trackingType', 'detail.trackingUnit']);
    }

    private function assertPending(FollowUpItem $item): void
    {
        if (! in_array($item->state, ['upcoming', 'due', 'in_progress', 'overdue'], true)) {
            throw new ApiConflictException('The follow-up item is no longer pending.', 'follow_up_item_state_conflict', 'follow_up_item', (string) $item->id);
        }
    }

    private function assertOperation(FollowUpItem $item, User $actor, string $operationId, string $type): ?FollowUpItem
    {
        $existing = FollowUpItem::query()->where('last_client_operation_id', $operationId)->first();
        if ($existing === null) {
            return null;
        }
        if ((string) $existing->id !== (string) $item->id || (string) $existing->last_operation_by_user_id !== (string) $actor->id || $existing->last_operation_type !== $type) {
            throw new ApiConflictException('The client operation id is already used by another operation.', 'idempotency_key_reused', 'client_operation_id', $operationId);
        }

        if ($type === 'reschedule') {
            return FollowUpItem::query()->where('rescheduled_from_id', $item->id)->latest('created_at')->firstOrFail();
        }

        return $this->load($item);
    }
}
