<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LiveSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'halaqa_id', 'teacher_id', 'student_id', 'follow_up_item_id', 'task_type_id', 'state', 'scheduled_at', 'requested_at', 'accepted_at', 'connected_at', 'ended_at', 'end_reason', 'direct_p2p_only', 'client_operation_id', 'last_client_operation_id', 'last_operation_by_user_id', 'last_operation_type'];

    protected function casts(): array
    {
        return ['direct_p2p_only' => 'boolean', 'scheduled_at' => 'datetime', 'requested_at' => 'datetime', 'accepted_at' => 'datetime', 'connected_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function followUpItem(): BelongsTo
    {
        return $this->belongsTo(FollowUpItem::class, 'follow_up_item_id');
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TrackingType::class, 'task_type_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(SessionTask::class, 'session_id')->orderBy('sequence_no');
    }

    public function report(): HasOne
    {
        return $this->hasOne(SessionReport::class, 'session_id');
    }

    public function outboxMessages(): HasMany
    {
        return $this->hasMany(RealtimeOutboxMessage::class, 'session_id');
    }
}
