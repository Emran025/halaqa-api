<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyTracking extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'membership_id', 'student_id', 'date', 'attendance_type', 'note', 'behavior_note'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'behavior_note' => 'integer'];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(HalaqaMembership::class, 'membership_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TrackingDetail::class, 'tracking_id');
    }
}
