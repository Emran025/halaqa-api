<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FollowUpPlan extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'student_id', 'created_by_user_id', 'source_registration_request_id',
        'frequency', 'status', 'timezone', 'starts_on', 'ends_on', 'version',
        'approved_by_user_id', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'version' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(FollowUpPlanDetail::class, 'plan_id')->orderBy('sort_order');
    }
}
