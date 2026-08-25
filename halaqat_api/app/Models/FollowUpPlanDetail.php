<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpPlanDetail extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'plan_id', 'tracking_type_id', 'tracking_unit_id', 'amount', 'notes', 'sort_order'];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FollowUpPlan::class, 'plan_id');
    }

    public function trackingType(): BelongsTo
    {
        return $this->belongsTo(TrackingType::class, 'tracking_type_id');
    }

    public function trackingUnit(): BelongsTo
    {
        return $this->belongsTo(TrackingUnit::class, 'tracking_unit_id');
    }
}
