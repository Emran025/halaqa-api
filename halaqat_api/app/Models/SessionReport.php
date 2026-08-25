<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'session_id', 'state', 'summary', 'duration_seconds', 'total_tasks',
        'total_mistakes', 'mistake_counts', 'version', 'teacher_approved_by', 'teacher_approval_note',
        'teacher_approved_at', 'student_acknowledged_at', 'student_acknowledgment_note',
        'reopened_by', 'reopened_at', 'reopen_reason', 'last_client_operation_id',
        'last_operation_by_user_id', 'last_operation_type',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'duration_seconds' => 'integer',
            'total_tasks' => 'integer',
            'total_mistakes' => 'integer',
            'mistake_counts' => 'array',
            'version' => 'integer',
            'teacher_approved_at' => 'datetime',
            'student_acknowledged_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function teacherApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_approved_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
