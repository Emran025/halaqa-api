<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionTask extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'session_id', 'tracking_type_id', 'sequence_no', 'planned_from_unit_id', 'planned_to_unit_id', 'planned_amount', 'actual_amount', 'state', 'comment', 'score', 'gap', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['planned_amount' => 'float', 'actual_amount' => 'float', 'score' => 'integer', 'gap' => 'float', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function trackingType(): BelongsTo
    {
        return $this->belongsTo(TrackingType::class, 'tracking_type_id');
    }
}
