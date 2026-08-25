<?php

namespace App\Services\Notifications;

use App\Exceptions\ApiConflictException;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    public function create(User $recipient, string $type, string $title, string $body, array $payload, string $dedupeKey): Notification
    {
        $normalizedPayload = [
            'event_type' => $payload['event_type'] ?? null,
            'entity_type' => $payload['entity_type'],
            'entity_id' => (string) $payload['entity_id'],
            'session_id' => isset($payload['session_id']) && $payload['session_id'] !== null ? (string) $payload['session_id'] : null,
            'follow_up_item_id' => isset($payload['follow_up_item_id']) && $payload['follow_up_item_id'] !== null ? (string) $payload['follow_up_item_id'] : null,
            'action' => $payload['action'],
            'action_path' => $payload['action_path'] ?? null,
        ];

        $existing = Notification::query()->where('dedupe_key', $dedupeKey)->first();
        if ($existing !== null) {
            if ((string) $existing->user_id !== (string) $recipient->id || $existing->type !== $type || $existing->payload !== $normalizedPayload) {
                throw new ApiConflictException('The notification dedupe key is already used for a different notification.', 'notification_dedupe_conflict', 'dedupe_key', $dedupeKey);
            }

            return $existing;
        }

        return Notification::create(['id' => (string) Str::uuid(), 'user_id' => $recipient->id, 'type' => $type, 'title' => $title, 'body' => $body, 'payload' => $normalizedPayload, 'dedupe_key' => $dedupeKey]);
    }

    public function list(User $user, array $filters): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->when(($filters['unread_only'] ?? false) === true, fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function markRead(User $user, Notification $notification): void
    {
        DB::transaction(function () use ($user, $notification): void {
            $locked = Notification::query()->whereKey($notification->id)->lockForUpdate()->firstOrFail();
            if ((string) $locked->user_id !== (string) $user->id) {
                return;
            }
            if ($locked->read_at === null) {
                $locked->update(['read_at' => now()]);
            }
        });
    }

    public function markAllRead(User $user): void
    {
        Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
