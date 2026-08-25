<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpItem extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'plan_id', 'plan_detail_id', 'student_id', 'halaqa_id', 'scheduled_for', 'timezone', 'state', 'completed_at', 'skipped_at', 'skip_reason', 'rescheduled_from_id', 'notification_sent_at'];

    protected function casts(): array
    {
        return ['id' => 'string', 'scheduled_for' => 'datetime', 'completed_at' => 'datetime', 'skipped_at' => 'datetime', 'notification_sent_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FollowUpPlan::class, 'plan_id');
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(FollowUpPlanDetail::class, 'plan_detail_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function halaqa(): BelongsTo
    {
        return $this->belongsTo(Halaqa::class, 'halaqa_id');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }
}
