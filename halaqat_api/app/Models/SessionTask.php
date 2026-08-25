<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SessionTask extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'session_id', 'client_operation_id', 'tracking_type_id', 'sequence_no', 'planned_from_unit_id', 'planned_to_unit_id', 'start_page', 'start_ayah_id', 'end_page', 'end_ayah_id', 'current_page', 'current_ayah_id', 'last_draft_operation_id', 'planned_amount', 'actual_amount', 'state', 'comment', 'score', 'gap', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['start_page' => 'integer', 'start_ayah_id' => 'integer', 'end_page' => 'integer', 'end_ayah_id' => 'integer', 'current_page' => 'integer', 'current_ayah_id' => 'integer', 'planned_amount' => 'float', 'actual_amount' => 'float', 'score' => 'integer', 'gap' => 'float', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function trackingDetail(): HasOne
    {
        return $this->hasOne(TrackingDetail::class, 'session_task_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(TaskEvaluation::class, 'session_task_id');
    }

    public function trackingType(): BelongsTo
    {
        return $this->belongsTo(TrackingType::class, 'tracking_type_id');
    }
}
