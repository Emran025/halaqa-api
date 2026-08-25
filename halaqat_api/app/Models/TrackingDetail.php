<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingDetail extends Model
{
    protected $fillable = ['uuid', 'tracking_id', 'session_task_id', 'tracking_type_id', 'from_unit_id', 'to_unit_id', 'actual_amount', 'state', 'comment', 'score', 'gap'];

    protected function casts(): array
    {
        return ['actual_amount' => 'float', 'score' => 'integer', 'gap' => 'float'];
    }

    public function tracking(): BelongsTo
    {
        return $this->belongsTo(DailyTracking::class, 'tracking_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SessionTask::class, 'session_task_id');
    }

    public function trackingType(): BelongsTo
    {
        return $this->belongsTo(TrackingType::class, 'tracking_type_id');
    }
}
