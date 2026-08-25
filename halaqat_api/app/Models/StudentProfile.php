<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'memorization_level', 'review_level', 'memorized_juz_count', 'memorized_surah_ids', 'last_completed_unit',
        'previous_memorization_notes', 'stop_reasons', 'bio',
    ];

    protected function casts(): array
    {
        return [
            'memorized_juz_count' => 'decimal:1',
            'memorized_surah_ids' => 'array',
            'last_completed_unit' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function availability(): HasOne
    {
        return $this->hasOne(StudentAvailabilityProfile::class, 'student_id', 'user_id');
    }

    public function followUpPlan(): HasOne
    {
        return $this->hasOne(FollowUpPlan::class, 'student_id', 'user_id')->latestOfMany();
    }
}
