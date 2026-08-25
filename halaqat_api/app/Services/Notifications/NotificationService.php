<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
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
